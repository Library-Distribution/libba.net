<?php
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../util.php");

	try
	{
		$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

		if ($request_method == "GET")
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
				$db_cond .= " user = UNHEX('" . user_get_id_by_name($_GET["user"]) . "')";
			}
			if (isset($_GET["name"]))
			{
				$db_cond .= ($db_cond) ? " AND" : " WHERE";
				$db_cond .= " name = '" . mysql_real_escape_string($_GET["name"], $db_connection) . "'";
			}
			if (isset($_GET["tags"]))
			{
				$db_cond .= ($db_cond) ? " AND" : " WHERE";
				$db_cond .= " tags REGEXP '(^|;)" . mysql_real_escape_string($_GET["tags"], $db_connection) . "($|;)'";
			}
			if (isset($_GET["default"]) && $_GET["default"] && strtolower($_GET["default"]) != "false")
			{
				$db_cond .= ($db_cond) ? " AND" : " WHERE";
				$db_cond .= " default_include = '1'";
			}
			$latest_only = isset($_GET["latest"]) && $_GET["latest"] && strtolower($_GET["latest"]) != "false";

			# retrieve data limits
			$db_limit = "";
			if (isset($_GET["count"]) && strtolower($_GET["count"]) != "all" && !$latest_only) # if "latest" is set, the data is shortened after being filtered
			{
				$db_limit = "LIMIT " . mysql_real_escape_string($_GET["count"], $db_connection);
			}
			if (isset($_GET["start"]) && !$latest_only) # if "latest" is set, the data is shortened after being filtered
			{
				if (!$db_limit)
				{
					$db_limit = "LIMIT 18446744073709551615"; # Source: http://dev.mysql.com/doc/refman/5.5/en/select.html
				}
				$db_limit .= " OFFSET " .  mysql_real_escape_string($_GET["start"], $db_connection);
			}

			# query data
			$db_query = "SELECT name, HEX(id), version, HEX(user) FROM $db_table_main $db_cond $db_limit";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				throw new HttpException(500);
			}

			# parse data to array
			$data = array();
			$users = array();
			while ($item = mysql_fetch_assoc($db_result))
			{
				$item["id"] = $item["HEX(id)"];
				unset($item["HEX(id)"]);

				$item["userID"] = $item["HEX(user)"];
				if (!isset($users[$item["userID"]]))
				{
					$users[$item["userID"]] = user_get_name($item["userID"]);
				}
				$item["user"] = $users[$item["userID"]];
				unset($item["HEX(user)"]);

				$data[] = $item;
			}

			if ($latest_only)
			{
				$versions = array();
				foreach ($data AS $index => $item) # go through all items and filter
				{
					$name = $item["name"];
					if (isset($versions[$name])) # a version of this item has already been processed
					{
						if ($versions[$name] > $item["version"]) # the other version is larger - delete the current item from output
						{
							unset($data[$index]);
						}
						else # the other version is lower - find it in the $data array and delete it from there
						{
							$old_index = searchSubArray($data, array("name" => $name, "version" => $versions[$name]));
							unset($data[$old_index]);
							$versions[$name] = $item["version"]; # indicate this version as the latest being processed
						}
					}
					else # no version has yet been processed, indicate this one as first
						$versions[$name] = $item["version"];
				}
				sort($data); # sort to have a continuing index

				# shorten data as specified by parameters
				$offset = 0;
				if (isset($_GET["start"]))
				{
					$offset = $_GET["start"];
				}
				if (isset($_GET["count"]) && strtolower($_GET["count"]) != "all")
				{
					$data = array_slice($data, $offset, $_GET["count"]);
				}
				else
				{
					$data = array_slice($data, $offset);
				}
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
					$content .= "<ald:item ald:name=\"{$item['name']}\" ald:version=\"{$item['version']}\" ald:id=\"{$item['id']}\" ald:user-id=\"{$item['userID']}\" ald:user=\"{$item['user']}\"/>";
				}
				$content .= "</ald:item-list>";
			}

			header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
			header("Content-type: $content_type");
			echo $content;
			exit;
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
?>
