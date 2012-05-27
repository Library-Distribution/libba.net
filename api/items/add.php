<?php
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../util.php");

	try
	{
		$request_method = strtoupper($_SERVER["REQUEST_METHOD"]);

		if ($request_method == "POST")
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
				$pack_id = $data["id"]; $pack_name = $data["name"]; $pack_version = $data["version"]; $pack_type = $data["type"];
				$pack_description = $data["description"];

				$pack_tags = array();
				foreach ($data["tags"] AS $tag)
				{
					$pack_tags[] = $tag["name"];
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

				if ($content_type == "application/json")
				{
					$content = "{ \"id\" : \"$pack_id\" }";
				}
				else if ($content_type == "text/xml" || $content_type == "application/xml")
				{
					$content = "<ald:item-id xmlns:ald='ald:/api/items/add/schema/2012' id='$pack_id'/>";
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
			throw new HttpException(405, array("Allow" => "POST"));
		}
	}
	catch (HttpException $e)
	{
		handleHttpException($e);
	}
?>
