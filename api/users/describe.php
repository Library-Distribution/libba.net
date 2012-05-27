<?php
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../util.php");

	try
	{
		$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

		if ($request_method == "GET")
		{
			if (isset($_GET["name"]))
			{
				# validate accept header of request
				$content_type = get_preferred_mimetype(array("application/json", "text/xml", "application/xml"), "application/json");

				# connect to database server
				$db_connection = db_ensure_connection();

				$db_query = "SELECT nick, mail, pw, privileges, joined FROM $db_table_users WHERE nick = '" . mysql_real_escape_string($_GET["name"], $db_connection) . "'";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				if (mysql_num_rows($db_result) == 1)
				{
					$user = mysql_fetch_assoc($db_result);
					if (!isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"]) || $_SERVER["PHP_AUTH_USER"] != $_GET["name"] || hash("sh256", $_SERVER["PHP_AUTH_PW"]) != $user["pw"])
					{
						$user["mail"] = md5($user["mail"]);
					}
					unset($user["pw"]);

					if ($content_type == "application/json")
					{
						$user["name"] = $user["nick"]; unset($user["nick"]);
						$content = json_encode($user);
					}
					else if ($content_type == "text/xml" || $content_type == "application/xml")
					{
						$content = "<ald:user xmlns:ald=\"ald://api/users/describe/schema/2012\" ald:name=\"{$user["nick"]}\" ald:mail=\"{$user["mail"]}\" ald:joined=\"{$user["joined"]}\" ald:privileges=\"{$user["privileges"]}\"/>";
					}

					header("HTTP/1.1 200 " . HttpException::getStatusMessage(200));
					header("Content-type: $content_type");
					echo $content;
					exit;
				}
				throw new HttpException(404);
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
?>