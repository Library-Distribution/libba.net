<?php
	require_once("db.php");
	require_once("HttpException.php");

	/* [deprecated] */
	function validateLogin($user, $pw)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$pw = hash("sha256", $pw);
		$escaped_user = mysql_real_escape_string($user, $db_connection);

		$db_query = "SELECT pw, activationToken FROM $db_table_users WHERE name = '$escaped_user'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		$data = mysql_fetch_object($db_result);
		if ($data->activationToken)
		{
			throw new HttpException(403, NULL, "Account is currently deactivated.");
		}
		if ($data->pw != $pw)
		{
			throw new HttpException(403, NULL, "Invalid credentials were specified.");
		}
	}

	function user_basic_auth($realm)
	{
		if (!isset($_SERVER["HTTPS"]) || !$_SERVER["HTTPS"])
		{
			throw new HttpException(403, NULL, "Must use HTTPS for authenticated APIs");
		}
		if (empty($_SERVER["PHP_AUTH_USER"]) || empty($_SERVER["PHP_AUTH_PW"]))
		{
			throw new HttpException(401, array("WWW-Authenticate" => "Basic realm=\"$realm\""));
		}
		validateLogin($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);
	}

	/* [deprecated] */
	function user_get_name($id)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT name FROM $db_table_users WHERE id = UNHEX('" . mysql_real_escape_string($id) . "')";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_object($db_result))
		{
			return $data->name;
		}
		throw new HttpException(404);
	}

	/* [deprecated] */
	function user_get_id_by_name($name)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT HEX(id) FROM $db_table_users WHERE name = '" . mysql_real_escape_string($name) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_assoc($db_result))
		{
			return $data["HEX(id)"];
		}
		throw new HttpException(404);
	}

	function read_package($package, $include_data = NULL)
	{
		static $all_data = NULL;
		if ($all_data == NULL)
		{
			$all_data = array("id", "name", "version", "type", "description", "authors", "dependencies", "requirements", "tags");
		}

		if ($include_data == NULL)
		{
			$include_data = $all_data;
		}

		$output = array();

		$archive = new ZipArchive();
		if (@$archive->open($package) != TRUE)
		{
			$archive->close();
			throw new HttpException(500, NULL, "Package file could not be opened!");
		}

		$doc = new DOMDocument();
		@$doc->loadXML($archive->getFromName("definition.ald"));

		if (!@$doc->schemaValidate(dirname(__DIR__) . "/schema/2012/package.xsd")) # $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "schema.xsd")) # temporary disabled because of ahk4.net config issue
		{
			throw new HttpException(400, NULL, "Package definition is not valid!");
		}

		$xp = new DOMXPath($doc);
		$xp->registerNamespace("ald", "ald://package/schema/2012");

		# check if all mentioned files are present
		if (!package_check_for_files($archive, $xp->query("/*/ald:files/ald:doc/@ald:path"), $error_file)
			|| !package_check_for_files($archive, $xp->query("/*/ald:files/ald:src/@ald:path"), $error_file)
			|| !package_check_for_files($archive, $xp->query("@ald:logo-image"), $error_file))
		{
			$archive->close();
			throw new HttpException(400, NULL, "Package references missing file: '" . $error_file . "'!");
		}

		if (in_array("id", $include_data))
		{
			$output["id"] = $xp->query("@ald:id")->item(0)->nodeValue;
		}
		if (in_array("name", $include_data))
		{
			$output["name"] = $xp->query("@ald:name")->item(0)->nodeValue;
		}
		if (in_array("version", $include_data))
		{
			$output["version"] = $xp->query("@ald:version")->item(0)->nodeValue;
		}
		if (in_array("type", $include_data))
		{
			$output["type"] = $xp->query("@ald:type")->item(0)->nodeValue;
		}
		if (in_array("description", $include_data))
		{
			$output["description"] = $xp->query("ald:description")->item(0)->nodeValue;
		}
		if (in_array("authors", $include_data))
		{
			$output["authors"] = array();
			foreach ($xp->query("/*/ald:authors/ald:author") AS $author_node)
			{
				$author = array();

				$author["name"] = get_first_attribute($xp, $author_node, "@ald:name");
				$temp = get_first_attribute($xp, $author_node, "@ald:user-name") AND $author["user-name"] = $temp;
				$temp = get_first_attribute($xp, $author_node, "@ald:homepage") AND $author["homepage"] = $temp;
				$temp = get_first_attribute($xp, $author_node, "@ald:email") AND $author["email"] = $temp;

				$output["authors"][] = $author;
			}
		}
		if (in_array("dependencies", $include_data))
		{
			$output["dependencies"] = array();
			foreach ($xp->query("/*/ald:dependencies/ald:dependency") AS $dep_node)
			{
				$dependency = array();

				$dependency["name"] = get_first_attribute($xp, $dep_node, "@ald:name");
				read_version_switch($xp, $dep_node, $dependency);

				$output["dependencies"][] = $dependency;
			}
		}
		if (in_array("requirements", $include_data))
		{
			$output["requirements"] = array();
			foreach ($xp->query("/*/ald:requirements/ald:requirement") AS $req_node)
			{
				$requirement = array();

				$requirement["type"] = get_first_attribute($xp, $req_node, "@ald:type");
				read_version_switch($xp, $req_node, $requirement);

				$output["requirements"][] = $requirement;
			}
		}
		# files
		if (in_array("tags", $include_data))
		{
			$output["tags"] = array();
			foreach ($xp->query("/*/ald:tags/ald:tag") AS $tag_node)
			{
				$output["tags"][] = array("name" => get_first_attribute($xp, $tag_node, "@ald:name"));
			}
		}
		if (in_array("links", $include_data))
		{
			$output["links"] = array();
			foreach ($xp->query("/*/ald:links/ald:link") AS $link_node)
			{
				$output["links"][] = array( "name" => get_first_attribute($xp, $link_node, "@ald:name"),
									"description" => get_first_attribute($xp, $link_node, "@ald:description"),
									"href" => get_first_attribute($xp, $link_node, "@ald:href"));
			}
		}
		# ...

		$archive->close();
		return $output;
	}

	function get_first_attribute($xp, $elem, $attr)
	{
		foreach ($xp->query($attr, $elem) AS $node)
		{
			return $node->nodeValue;
		}
		return NULL;
	}

	function read_version_switch($xp, $node, &$output)
	{
		if ($list = $xp->query("ald:version-list", $node)->item(0))
		{
			$output["version-list"] = array();
			foreach ($xp->query("ald:version/@ald:value", $range) AS $version)
			{
				$output["version-list"][] = $version->nodeValue;
			}
		}
		else if ($range = $xp->query("ald:version-range", $node)->item(0))
		{
			$output["version-range"] = array();
			$temp = get_first_attribute($xp, $range, "@ald:min-version") AND $output["version-range"]["min"] = $temp;
			$temp = get_first_attribute($xp, $range, "@ald:max-version") AND $output["version-range"]["max"] = $temp;
		}
		else
		{
			$output["version"] = $xp->query("ald:version/@ald:value", $node)->item(0)->nodeValue;
		}
	}

	function package_check_for_files($archive, $file_list, &$error_file = NULL)
	{
		foreach ($file_list AS $file_entry)
		{
			if (!$archive->locateName($file_entry->nodeValue))
			{
				$error_file = $file_entry->nodeValue;
				return false;
			}
		}
		return true;
	}

	function find_free_file($dir = "", $ext = "")
	{
		do
		{
			$file = rand().$ext;
		} while(file_exists($dir . $file));
		return $dir . $file;
	}

	function find_free_directory($parent = "")
	{
		do
		{
			$dir = rand();
		} while(is_dir($parent . $dir));
		return $parent . $dir . DIRECTORY_SEPARATOR;
	}

	function ensure_upload_dir()
	{
		$dir = upload_dir_path();
		if (!is_dir($dir))
		{
			mkdir($dir);
		}
	}

	function upload_dir_path()
	{
		#return $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR; # temporary disabled because of ahk4.net configuration issue
		return dirname(__DIR__) . "/uploads/";
	}

	function get_preferred_mimetype($available, $default)
	{
		if (isset($_SERVER["HTTP_ACCEPT"]))
		{
			foreach(explode(",", $_SERVER["HTTP_ACCEPT"]) as $value)
			{
				$acceptLine = explode(";", $value);
				if (in_array($acceptLine[0], $available))
				{
					return $acceptLine[0];
				}
			}
			throw new HttpException(406, array("Content-type" => implode($available, ",")));
		}
		return $default;
	}

	function ensure_HTTPS()
	{
		if (!$_SERVER["HTTPS"])
		{
			throw new HttpException(403, NULL, "Must use HTTPS!");
		}
	}

	function handleHttpException($e)
	{
		header("HTTP/1.1 " . $e->getCode() . " " . HttpException::getStatusMessage($e->getCode()));
		if (is_array($e->getHeaders()))
		{
			foreach ($e->getHeaders() AS $header => $value)
			{
				header($header . ": " . $value);
			}
		}
		echo "ERROR: " . $e->getCode() . " - " . $e->getMessage();
	}

	# SOURCE: http://www.php.net/manual/de/function.rmdir.php#108113
	# recursively remove a directory
	function rrmdir($dir) {
		foreach(glob($dir . "/*") as $file) {
			if(is_dir($file))
				rrmdir($file);
			else
				unlink($file);
		}
		rmdir($dir);
	}

	# SOURCE: http://stackoverflow.com/a/6303043
	# modified to allow an array of key-value pairs instead of just one
	function searchSubArray(Array $array, $key_values)
	{
		foreach ($array as $index => $subarray)
		{
			$match = true;
			foreach ($key_values AS $key => $value)
			{
				$match = $match && (isset($subarray[$key]) && $subarray[$key] == $value);
			}
			if ($match)
				return $index;
		}
	}
?>