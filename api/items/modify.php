<?php
	require_once("../util.php");
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../User.php");

	try
	{
		user_basic_auth("Restricted API");

		$request_method = strtoupper($_SERVER["REQUEST_METHOD"]);
		if ($request_method == "POST")
		{
			if (isset($_GET["id"]) || (isset($_GET["name"]) && isset($_GET["version"])))
			{
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
					$id = mysql_real_escape_string($_GET["id"], $db_connection);
				}

				if (!empty($_POST["user"]))
				{
					if  (!User::hasPrivilege($_SERVER["PHP_AUTH_USER"], User::PRIVILEGE_ADMIN)) # not an admin
					{
						$db_query = "SELECT HEX(user) FROM $db_table_main WHERE id = UNHEX('$id')";
						$db_result = mysql_query($db_query, $db_connection);

						if (!$db_result)
						{
							throw new HttpException(500);
						}
						if (mysql_num_rows($db_result) != 1)
						{
							throw new HttpException(404);
						}

						$data = mysql_fetch_assoc($db_result);
						if ($data["HEX(user)"] != User::getID($_SERVER["PHP_AUTH_USER"])) # neither admin nor the user who had uploaded the item - not allowed
						{
							throw new HttpException(403);
						}
					}

					$db_query = "UPDATE $db_table_main Set user = UNHEX('" . User::getID($_POST["user"]) . "') WHERE id = UNHEX('$id')";
					if (!mysql_query($db_query, $db_connection))
					{
						throw new HttpException(500);
					}
					if (mysql_affected_rows() != 1)
					{
						throw new HttpException(404);
					}
				}
				if (isset($_POST["reviewed"]))
				{
					if (!User::hasPrivilege($_SERVER["PHP_AUTH_USER"], User::PRIVILEGE_REVIEW))
					{
						throw new HttpException(403);
					}
					if (!in_array((int)$_POST["reviewed"], array(-1, 0, 1)))
					{
						throw new HttpException(400);
					}

					$db_query = "UPDATE $db_table_main Set reviewed = '" . mysql_real_escape_string($_POST["reviewed"]) . "' WHERE id = UNHEX('$id')";
					if (!mysql_query($db_query, $db_connection))
					{
						throw new HttpException(500);
					}
					if (mysql_affected_rows() != 1)
					{
						throw new HttpException(404);
					}
				}
				if (isset($_POST["default"]))
				{
					if (!User::hasPrivilege($_SERVER["PHP_AUTH_USER"], User::PRIVILEGE_DEFAULT_INCLUDE))
					{
						throw new HttpException(403);
					}
					if (!in_array((int)$_POST["default"], array(0, 1)))
					{
						throw new HttpException(400);
					}

					$db_query = "UPDATE $db_table_main Set default_include = '" . mysql_real_escape_string($_POST["default"]) . "' WHERE id = UNHEX('$id')";
					if (!mysql_query($db_query, $db_connection))
					{
						throw new HttpException(500);
					}
					if (mysql_affected_rows() != 1)
					{
						throw new HttpException(404);
					}
				}
				header("HTTP/1.1 204 " . HttpException::getStatusMessage(204));
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