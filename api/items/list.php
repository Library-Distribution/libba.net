<?php
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../util.php");
	require_once("../User.php");
	require_once("../semver.php");

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
				$db_cond .= " user = UNHEX('" . User::getID($_GET["user"]) . "')";
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

			# items in or not in the stdlib
			# ================================ #
			if (isset($_GET["stdlib"]) && in_array(strtolower($_GET["stdlib"]), array("no", "false", "-1")))
			{
				$db_cond .= ($db_cond) ? " AND " : " WHERE ";
				$db_cond .= "default_include = '0'";
			}
			else if (isset($_GET["stdlib"]) && in_array(strtolower($_GET["stdlib"]), array("yes", "true", "+1", "1")))
			{
				$db_cond .= ($db_cond) ? " AND" : " WHERE ";
				$db_cond .= "default_include = '1'";
			}
			/* else {} */ # default (use "both" or "0") - leave empty so both match
			# ================================ #

			# reviewed and unreviewed items
			# ================================ #
			$db_cond .= ($db_cond) ? " AND " : " WHERE ";
			if (isset($_GET["reviewed"]) && in_array(strtolower($_GET["reviewed"]), array("no", "false", "-1")))
			{
				$db_cond .= "reviewed = '0'";
			}
			else if (isset($_GET["reviewed"]) && in_array(strtolower($_GET["reviewed"]), array("both", "0")))
			{
				$db_cond .= "reviewed = '0' OR reviewed = '1'";
			}
			else # default (use "yes", "true", "+1" or "1")
			{
				$db_cond .= "reviewed = '1'";
			}
			# ================================ #

			if (isset($_GET["version"]))
			{
				$version = strtolower($_GET["version"]);
				if (!in_array($version, array("latest", "first")))
				{
					throw new HttpException(400);
				}
			}

			# retrieve data limits
			$db_limit = "";
			if (isset($_GET["count"]) && strtolower($_GET["count"]) != "all" && !isset($version)) # if version ("latest" or "first") is set, the data is shortened after being filtered
			{
				$db_limit = "LIMIT " . mysql_real_escape_string($_GET["count"], $db_connection);
			}
			if (isset($_GET["start"]) && !isset($version)) # if version ("latest" or "first") is set, the data is shortened after being filtered
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

				if (!isset($users[$item["HEX(user)"]]))
				{
					$users[$item["HEX(user)"]] = User::getName($item["HEX(user)"]);
				}
				$item["user"] = array("name" => $users[$item["HEX(user)"]], "id" => $item["HEX(user)"]);
				unset($item["HEX(user)"]);

				$data[] = $item;
			}

			if (isset($version))
			{
				$versions = array();
				foreach ($data AS $index => $item) # go through all items and filter
				{
					$name = $item["name"];
					if (isset($versions[$name])) # a version of this item has already been processed
					{
						if (($version == "latest" && semver_compare($versions[$name], $item["version"]) == 1) || ($version == "first" && semver_compare($versions[$name], $item["version"]) == -1)) # the other version is higher/lower - delete the current item from output
						{
							unset($data[$index]);
						}
						else # the other version is lower/higher - find it in the $data array and delete it from there
						{
							$other_index = searchSubArray($data, array("name" => $name, "version" => $versions[$name]));
							unset($data[$other_index]);
							$versions[$name] = $item["version"]; # indicate this version as the latest / oldest being processed
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
					$content .= "<ald:item ald:name=\"{$item['name']}\" ald:version=\"{$item['version']}\" ald:id=\"{$item['id']}\" ald:user-id=\"{$item['user']['id']}\" ald:user=\"{$item['user']['name']}\"/>";
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
