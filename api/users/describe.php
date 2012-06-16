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

				$db_query = "SELECT name, HEX(id), mail, pw, privileges, joined FROM $db_table_users WHERE name = '" . mysql_real_escape_string($_GET["name"], $db_connection) . "'";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500);
				}

				if (mysql_num_rows($db_result) == 1)
				{
					$user = mysql_fetch_assoc($db_result);
					$encrypt_mail = true;

					if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"]))
					{
						if (!isset($_SERVER["HTTPS"]) || !$_SERVER["HTTPS"])
						{
							throw new HttpException(403, NULL, "Must use HTTPS for authenticated APIs");
						}

						$password_hash = hash("sha256", $_SERVER["PHP_AUTH_PW"]);

						if ($_SERVER["PHP_AUTH_USER"] == $user["name"] && $password_hash == $user["pw"]) # user requests information about himself - OK.
						{
							$encrypt_mail = false;
						}
						else
						{
							$db_query = "SELECT pw, privileges FROM $db_table_users WHERE name = '" . mysql_real_escape_string($_SERVER["PHP_AUTH_USER"], $db_connection) . "'";
							$db_result = mysql_query($db_query, $db_connection);
							if (!$db_result)
							{
								throw new HttpException(500);
							}

							if (mysql_num_rows($db_result) == 1)
							{
								$request_user = mysql_fetch_assoc($db_result);
								if ($request_user["privileges"] > 0 && $password_hash == $request_user["pw"]) # user has advanced privileges - OK.
								{
									$encrypt_mail = false;
								}
							}
						}
					}
					if ($encrypt_mail)
					{
						$user["mail"] = md5($user["mail"]);
					}
					unset($user["pw"]);
					$user["id"] = $user["HEX(id)"]; unset($user["HEX(id)"]);

					if ($content_type == "application/json")
					{
						$content = json_encode($user);
					}
					else if ($content_type == "text/xml" || $content_type == "application/xml")
					{
						$content = "<ald:user xmlns:ald=\"ald://api/users/describe/schema/2012\" ald:name=\"{$user["name"]}\" ald:mail=\"{$user["mail"]}\" ald:joined=\"{$user["joined"]}\" ald:privileges=\"{$user["privileges"]}\" ald:id=\"{$user["id"]}\"/>";
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