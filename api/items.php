<?php
	require_once("HttpException.php");
	require_once("db.php");
	require_once("util.php");

	try
	{
		$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

		if ($request_method == "GET") # output a list of items
		{
			if (isset($_GET["id"]) || (isset($_GET["name"]) && isset($_GET["version"])))
			{
				# validate accept header of request
				$content_type = get_preferred_mimetype(array("application/json", "text/xml", "application/xml"), "application/json");

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
					$id = $_GET["id"];
				}

				$db_query = "SELECT *, HEX(user) FROM $db_table_main WHERE id = UNHEX('$id')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}
				if (mysql_num_rows($db_result) != 1)
				{
					throw new HttpException(404, NULL, "read!");
				}
				$db_entry = mysql_fetch_assoc($db_result);

				$data = read_package(upload_dir_path() . $db_entry["file"]);

				$output = $data;
				$output["uploaded"] = $db_entry["uploaded"];
				$output["user"] = user_get_nick($db_entry["HEX(user)"]);
				$tag_list  = array();
				foreach ($data["tags"] AS $tag)
				{
					$tag_list[] = $tag["name"];
				}
				$output["tags"] = $tag_list;

				if ($content_type == "application/json")
				{
					$content = json_encode($output);
				}
				else if ($content_type == "text/xml" || $content_type == "application/xml")
				{
					$content = "<ald:item xmlns:ald=\"ald://api/items/get/schema/2012\">";
					# ...
					$content .= "</ald:item>";
					throw new HttpException(501, NULL, "JSON can already be provided.");
				}

				header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
				header("Content-type: $content_type");
				echo $content;
				exit;
			}
			else # output list of items
			{
				# validate accept header of request
				$content_type = get_preferred_mimetype(array("application/json", "text/xml", "application/xml"), "application/json");

				# connect to database server
				$db_connection = db_ensure_connection();

				# retrieve conditions for returned data from GET parameters
				$db_cond = "";
				if (isset($_GET["type"]))
				{
					$db_cond = "WHERE type = '" . mysql_real_escape_string($_GET["type"], $db_connection) . "'";
				}
				if (isset($_GET["user"]))
				{
					$db_cond .= ($db_cond) ? " AND" : " WHERE";
					$db_cond .= " user = '" . mysql_real_escape_string($_GET["user"], $db_connection) . "'";
				}
				if (isset($_GET["name"]))
				{
					$db_cond .= ($db_cond) ? " AND" : " WHERE";
					$db_cond .= " name = '" . mysql_real_escape_string($_GET["name"], $db_connection) . "'";
				}

				# retrieve data limits
				$db_limit = "";
				if (isset($_GET["count"]) && strtolower($_GET["count"]) != "all")
				{
					$db_limit = "LIMIT " . mysql_real_escape_string($_GET["count"], $db_connection);
				}
				if (isset($_GET["start"]))
				{
					if (!$db_limit)
					{
						$db_limit = "LIMIT 18446744073709551615"; # Source: http://dev.mysql.com/doc/refman/5.5/en/select.html
					}
					$db_limit .= " OFFSET " .  mysql_real_escape_string($_GET["start"], $db_connection);
				}

				# query data
				$db_query = "SELECT name, HEX(id), version FROM $db_table_main $db_cond $db_limit";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				# parse data to array
				$data = array();
				while ($item = mysql_fetch_assoc($db_result))
				{
					$data[] = $item;
				}

				# return content-type specific data
				if ($content_type == "application/json")
				{
					$content = json_encode($data);
				}
				else if ($content_type == "text/xml" || $content_type == "application/xml")
				{
					$content = "<ald:item-list xmlns:ald=\"ald://api/items/list/schema/2012\">";
					foreach ($data AS $item)
					{
						 $content .= "<ald:item name=\"{$item['name']}\" version=\"{$item['version']}\" id=\"{$item['HEX(id)']}\"/>";
					}
					$content .= "</ald:item-list>";
				}

				header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
				header("Content-type: $content_type");
				echo $content;
				exit;
			}
			throw new HttpException(400);
		}
		else if ($request_method == "POST")
		{
			# authentication
			user_basic_auth("Restricted API");
			$user = $_SERVER["PHP_AUTH_USER"];

			if (isset($_FILES["package"]))
			{
				# validate accept header of request
				$content_type = get_preferred_mimetype(array("application/json", "text/xml", "application/xml"), "application/json");

				$pack_file = $_FILES["package"];

				# connect to database server
				$db_connection = db_ensure_connection();

				# upload and read file:
				###########################################################
				$file_size_limit = 75; # MB
				if ($pack_file["size"] > ($file_size_limit * 1024 * 1024))
				{
					throw new HttpException(413, NULL, "File must not be > $file_size_limit MB.");
				}

				ensure_upload_dir(); # ensure the directory for uploads exists
				$file = find_free_file(upload_dir_path(), ".zip");
				move_uploaded_file($pack_file["tmp_name"], $file);

				$data = read_package($file, array("id", "name", "version", "type", "description", "tags")); # todo: read and parse file
				$pack_id = $data["id"]; $pack_name = $data['name']; $pack_version = $data['version']; $pack_type = $data['type'];
				$pack_description = $data['description'];

				$pack_tags = array();
				foreach ($data['tags'] AS $tag)
				{
					$pack_tags[] = $tag['name'];
				}
				$pack_tags = implode(";", $pack_tags);

				# todo: validate version string / convert to number
				###########################################################

				date_default_timezone_set("UTC");
				$datetime = date("Y-m-d H:i:s");

				# escape data to prevent SQL injection
				$escaped_name = mysql_real_escape_string($pack_name, $db_connection);
				$escaped_type = mysql_real_escape_string($pack_type, $db_connection);
				$escaped_version = mysql_real_escape_string($pack_version, $db_connection);
				$escaped_description = mysql_real_escape_string($pack_description, $db_connection);
				$escaped_tags = mysql_real_escape_string($pack_tags, $db_connection);

				# check if there's any version of the app
				$db_query = "SELECT HEX(user) FROM $db_table_main WHERE name = '$escaped_name' LIMIT 1";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				if (mysql_num_rows($db_result) > 0)
				{
					# if so, check if it's the same user as now
					$db_entry = mysql_fetch_assoc($db_result);
					if (user_get_nick($db_entry["HEX(user)"]) != $user)
					{
						throw new HttpException(403, NULL, "The user '$user' is not allowed to update the library or app '$pack_name'");
					}
				}

				# check if this specific version had already been uploaded or not
				$db_query = "SELECT HEX(id) FROM $db_table_main WHERE name = '$escaped_name' AND version = '$escaped_version' LIMIT 1";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				if (mysql_num_rows($db_result) > 0)
				{
					throw new HttpException(409, NULL, "The specified version '$pack_version' of package '$pack_name' has already been uploaded!");
				}

				# check if item with this GUID had already been uploaded or not
				$db_query = "SELECT HEX(id) FROM $db_table_main WHERE id = UNHEX('$pack_id')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				if (mysql_num_rows($db_result) > 0)
				{
					throw new HttpException(409, NULL, "An item with the specified GUID '$pack_id' has already been uploaded!");
				}

				# add the database entry
				$db_query = "INSERT INTO $db_table_main (id, name, type, version, file, user, description, tags, uploaded)
							VALUES (UNHEX('$pack_id'), '$escaped_name', '$escaped_type', '$escaped_version', '".basename($file)."', UNHEX('" . user_get_id_by_nick($user) . "'), '$escaped_description', '$escaped_tags', '$datetime')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
				header("Content-type: $content_type");
				if ($content_type == "application/json")
				{
					$content = "{ \"id\" : \"$pack_id\" }";
				}
				else if ($content_type == "text/xml" || $content_type == "application/xml")
				{
					$content = "<ald:item-id xmlns:ald='ald:/api/items/upload/schema/2012' id='$pack_id'/>";
				}
				echo $content;
				exit;
			}
			throw new HttpException(400);
		}
		else if ($request_method == "DELETE")
		{
			# authentication
			user_basic_auth("Restricted API");
			$user = $_SERVER["PHP_AUTH_USER"];

			if (isset($_GET["id"]))
			{
				throw new HttpException(501);
			}
			throw new HttpException(400);
		}
		else
		{
			throw new HttpException(405, array("Allow" => "GET", "POST", "DELETE"));
		}
	}
	catch (HttpException $e)
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
?>