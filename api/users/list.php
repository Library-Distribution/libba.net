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

			# query for data:
			$db_query = "SELECT name, HEX(id) FROM $db_table_users $db_limit";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				throw new HttpException(500);
			}

			# parse data to array
			$data = array();
			while ($item = mysql_fetch_assoc($db_result))
			{
				$item["id"] = $item["HEX(id)"]; unset($item["HEX(id)"]);
				$data[] = $item;
			}

			# return content-type specific data
			if ($content_type == "application/json")
			{
				$content = json_encode($data);
			}
			else if ($content_type == "text/xml" || $content_type == "application/xml")
			{
				$content = "<ald:user-list xmlns:ald=\"ald://api/users/list/schema/2012\">";
				foreach ($data AS $item)
				{
					$content .= "<ald:user ald:name=\"{$item["name"]}\" ald:id=\"{$item["id"]}\"/>";
				}
				$content .= "</ald:user-list>";
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