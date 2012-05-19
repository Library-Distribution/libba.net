<?php
	require_once("HttpException.php");
	require_once("db.php");
	try
	{
		$allowed_content_types = array("application/json", "text/xml", "application/xml", "application/ald-package");
		$allowed_methods = array("get");

		$content_type = get_preferred_mimetype($allowed_content_types, "application/json");
		$method = strtolower($_SERVER['REQUEST_METHOD']);
		if (!in_array($method, $allowed_methods))
		{
			throw new HttpException(405, array("Allow" => explode(",", $allowed_methods)));
		}

		$db_connection = db_ensure_connection();
		if (isset($_GET["id"]))
		{
		}
		else if (isset($_GET["name"]) && isset($_GET["version"]))
		{
		}
		else
		{
			if ($content_type == "application/ald-package")
			{
				throw new HttpException(406, array("Content-type" => $allowed_content_types[0] . ","
																	. $allowed_content_types[1] . ","
																	. $allowed_content_types[2]));
			}

			$db_cond = "";
			if (isset($_GET["type"]))
			{
				$db_cond = "WHERE type = '" . mysql_real_escape_string($_GET["type"]) . "'";
			}
			if (isset($_GET["user"]))
			{
				$db_cond .= ($db_cond) ? " AND" : " WHERE";
				$db_cond .= " user = '" . mysql_real_escape_string($_GET["user"]) . "'";
			}
			if (isset($_GET["name"]))
			{
				$db_cond .= ($db_cond) ? " AND" : " WHERE";
				$db_cond .= " name = '" . mysql_real_escape_string($_GET["name"]) . "'";
			}

			$db_limit = "";
			if (isset($_GET["count"]) && strtolower($_GET["count"]) != "all")
			{
				$db_limit = "LIMIT " . mysql_real_escape_string($_GET["count"]);
			}
			if (isset($_GET["start"]))
			{
				if (!$db_limit)
				{
					$db_limit = "LIMIT 18446744073709551615"; # Source: http://dev.mysql.com/doc/refman/5.5/en/select.html
				}
				$db_limit .= " OFFSET " .  mysql_real_escape_string($_GET["start"]);
			}

			$db_query = "SELECT name, id, version FROM $db_table_main $db_cond $db_limit";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				throw new HttpException(500);
			}

			$data = array();
			while ($item = mysql_fetch_assoc($db_result))
			{
				$data[] = $item;
			}

			if ($content_type == "application/json")
			{
				$content = json_encode($data);
			}
			else if ($content_type == "text/xml" || $content_type == "application/xml")
			{
				$content = "<ald:item-list xmlns:ald=\"ald://package/schema/2012\">";
				foreach ($data AS $item)
				{
					 $content .= "<ald:item name=\"{$item['name']}\" version=\"{$item['version']}\" id=\"{$item['id']}\"/>";
				}
				$content .= "</ald:item-list>";
			}

			header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
			header("Content-type: $content_type");
			echo $content;
			exit;
		}
	}
	catch (HttpException $e)
	{
		header("HTTP/1.1 " . $e->code . " " . $e->getMessage());
		if (is_array($e->headers))
		{
			foreach ($e->headers AS $header => $value)
			{
				header($header . ": " . $value);
			}
			echo "ERROR: " . $e->code . " - " . $e->getMessage();
		}
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
			return NULL;
		}
		return $default;
	}
?>