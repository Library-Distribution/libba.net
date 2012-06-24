<?php
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../util.php");
	require_once("../User.php");

	try
	{
		$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

		if ($request_method == "GET")
		{
			if (isset($_GET["id"]) || (isset($_GET["name"]) && isset($_GET["version"])))
			{
				# validate accept header of request
				$content_type = get_preferred_mimetype(array("application/json", "text/xml", "application/xml", "application/x-ald-package"), "application/json");

				# connect to database server
				$db_connection = db_ensure_connection();

				if (!isset($_GET["id"]))
				{
					$db_query = "SELECT HEX(id) FROM $db_table_main WHERE name = '" . mysql_real_escape_string($_GET["name"], $db_connection) . "' AND version = '" . mysql_real_escape_string($_GET["version"], $db_connection) . "'";
					$db_result = mysql_query($db_query, $db_connection);

					if (!$db_result)
					{
						throw new HttpException(500);
					}
					if (mysql_num_rows($db_result) != 1)
					{
						throw new HttpException(404);
					}

					$db_entry = mysql_fetch_assoc($db_result);
					$id = $db_entry["HEX(id)"];
				}
				else
				{
					$id = mysql_real_escape_string($_GET["id"], $db_connection);
				}

				if ($content_type == "application/x-ald-package")
				{
					$db_query = "SELECT file FROM $db_table_main WHERE id = UNHEX('$id')";
				}
				else
				{
					$db_query = "SELECT *, HEX(user) FROM $db_table_main WHERE id = UNHEX('$id')";
				}

				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}
				if (mysql_num_rows($db_result) != 1)
				{
					throw new HttpException(404);
				}
				$db_entry = mysql_fetch_assoc($db_result);

				if ($content_type == "application/x-ald-package")
				{
					$file = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . $db_entry["file"];
					header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
					header("Content-Type: $content_type");
					header("Content-Length: " . filesize($file));
					header("Content-Disposition: attachment; filename=$id.alp");
					header("Content-MD5: " . base64_encode(md5_file($file)));
					@readfile($file);
					exit;
				}

				$data = read_package(upload_dir_path() . $db_entry["file"]);

				$output = $data;
				$output["uploaded"] = $db_entry["uploaded"];
				$output["userID"] = $db_entry["HEX(user)"];
				$output["user"] = User::getName($db_entry["HEX(user)"]);
				$tag_list  = array();
				foreach ($data["tags"] AS $tag)
				{
					$tag_list[] = $tag["name"];
				}
				$output["tags"] = $tag_list;

				if ($content_type == "application/json")
				{
					ksort($output);
					$content = json_encode($output);
				}
				else if ($content_type == "text/xml" || $content_type == "application/xml")
				{
					$content = "<ald:item xmlns:ald=\"ald://api/items/describe/schema/2012\"";
					# ...
					foreach ($output AS $key => $value)
					{
						if (!is_array($value))
						{
							$content .= " ald:$key=\"$value\"";
						}
					}
					$content .= ">";
					if (isset($output["authors"]) && is_array($output["authors"]))
					{
						$content .= "<ald:authors>";
						foreach ($output["authors"] AS $author)
						{
							$content .= "<ald:author ald:name=\"{$author["name"]}\""
											. (isset($author["user-name"]) ? " ald:user-name=\"{$author["user-name"]}\"" : "")
											. (isset($author["homepage"]) ? " ald:homepage=\"{$author["homepage"]}\"" : "")
											. (isset($author["mail"]) ? " ald:mail=\"{$author["mail"]}\"" : "")
									. "/>";
						}
						$content .= "</ald:authors>";
					}
					if (isset($output["dependencies"]) && is_array($output["dependencies"]))
					{
						$content .= "<ald:dependencies>";
						foreach ($output["dependencies"] AS $dependency)
						{
							$content .= "<ald:dependency ald:name=\"{$dependency["name"]}\">" . xml_version_switch($dependency) . "</ald:dependency>";
						}
						$content .= "</ald:dependencies>";
					}
					if (isset($output["requirements"]) && is_array($output["requirements"]))
					{
						$content .= "<ald:requirements>";
						foreach ($output["requirements"] AS $requirement)
						{
							$content .= "<ald:requirement ald:type=\"{$requirement["type"]}\">" . xml_version_switch($requirement) . "</ald:requirement>";
						}
						$content .= "</ald:requirements>";
					}
					if (isset($output["tags"]) && is_array($output["tags"]))
					{
						$content .= "<ald:tags>";
						foreach ($output["tags"] AS $tag)
						{
							$content .= "<ald:tag ald:name=\"{$tag}\"/>";
						}
						$content .= "</ald:tags>";
					}
					if (isset($output["links"]) && is_array($output["links"]))
					{
						$content .= "<ald:links>";
						foreach ($output["links"] AS $link)
						{
							$content .= "<ald:link ald:name=\"{$link["name"]}\" ald:description=\"{$link["description"]}\" ald:href=\"{$link["href"]}\"/>";
						}
						$content .= "</ald:links>";
					}
					$content .= "</ald:item>";
				}

				header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
				header("Content-type: $content_type");
				echo $content;
				exit;
			}
			else
			{
				throw new HttpException(400);
			}
		}
		else
		{
			throw new HttpException(405, array("Allow" => "GET"));
		}
	}
	catch (HttpException $e)
	{
		handleHttpException($e);
	}

	function xml_version_switch($data)
	{
		$content = "";
		if (isset($data["version"]))
		{
			$content .= "<ald:version ald:value=\"{$data["version"]}\"/>";
		}
		else if (isset($data["version-range"]))
		{
			$content .= "<ald:version-range ald:min-value=\"{$data["version-range"]["min"]}\" ald:max-value=\"{$data["version-range"]["max"]}\"/>";
		}
		else if (isset($data["version-list"]) && is_array($data["version-list"]))
		{
			$content .= "<ald:version-list>";
			foreach ($data["version-list"] AS $version)
			{
				$content .= "<ald:version ald:value=\"$version\"/>";
			}
			$content .= "</ald:version-list>";
		}
		return $content;
	}
?>
