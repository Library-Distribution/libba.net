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
				$db_cond .= " user = '" . mysql_real_escape_string($_GET["user"], $db_connection) . "'";
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
			$db_query = "SELECT name, HEX(id), version, HEX(user) FROM $db_table_main $db_cond $db_limit";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				throw new HttpException(500);
			}

			# parse data to array
			$data = array();
			$users = array();
			if ($latest_only)
			{
				$versions = array();
			}
			while ($item = mysql_fetch_assoc($db_result))
			{
				if ($latest_only)
				{
					if (isset($versions[$item["name"]]) && $versions[$item["name"]] > $item["version"])
					{
						continue;
					}
					$versions[$item["name"]] = $item["version"];
				}

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
