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

				require_once("../util.php");

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
					$content = "<ald:item-list xmlns:ald=\"ald://package/schema/2012\">";
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
			user_basic_auth("Restricted ALD API");

			if (isset($_FILES["package"]))
			{
				# connect to database server
				$db_connection = db_ensure_connection();

				# see upload.php...

				header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
				exit;
			}
			throw new HttpException(400);
		}
		else
		{
			throw new HttpException(405, array("Allow" => "GET", "POST"));
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

	function get_preferred_mimetype($available, $default)
	{
		if (isset($_SERVER['HTTP_ACCEPT']))
		{
			foreach(explode(",", $_SERVER['HTTP_ACCEPT']) as $value)
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
?>