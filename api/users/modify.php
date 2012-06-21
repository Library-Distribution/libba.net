<?php
	require_once("../util.php");
	require_once("../HttpException.php");
	require_once("../db.php");

	try
	{
		user_basic_auth("Restricted API");

		$request_method = strtoupper($_SERVER["REQUEST_METHOD"]);
		if ($request_method == "POST")
		{
			$db_connection = db_ensure_connection();

			if (!empty($_GET["name"]))
			{
				$db_query = "SELECT HEX(id) FROM $db_table_users WHERE name = '" . mysql_real_escape_string($_GET["name"], $db_connection) . "'";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500, NULL, "Failed to get user ID.");
				}
				if (mysql_num_rows($db_result) != 1)
				{
					throw new HttpException(404, NULL, "Unknown user name.");
				}
				$obj = mysql_fetch_assoc($db_result);
				$id = $obj["HEX(id)"];
			}
			else if (!empty($_GET["id"]))
			{
				$id = mysql_real_escape_string($_GET["id"], $db_connection);
			}

			if (!empty($_POST["name"]))
			{
				$db_query = "UPDATE $db_table_users Set name = '" . mysql_real_escape_string($_POST["name"], $db_connection) . "' WHERE id = UNHEX('$id')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500, NULL, "Failed to set user name.");
				}
				if (mysql_affected_rows($db_connection) != 1)
				{
					throw new HttpException(404, NULL, "User with this ID was not found.");
				}
			}
			if (!empty($_POST["mail"]))
			{
				$mail = mysql_real_escape_string($_POST["mail"], $db_connection);
				$token = mt_rand();

				$db_query = "UPDATE $db_table_users Set mail = '$mail', activationToken = '$token' WHERE id = UNHEX('$id')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500, NULL, "Failed to set user mail address.");
				}
				if (mysql_affected_rows($db_connection) != 1)
				{
					throw new HttpException(404, NULL, "User with this ID was not found.");
				}

				$url = "http://" . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'] . "?name=$name&mode=activate&token=$token";
				if (!mail($mail,
					"Confirm ALD email address change",
					"To reactivate your account, go to <a href='$url'>$url</a>.",
					"FROM: noreply@{$_SERVER['HTTP_HOST']}\r\nContent-type: text/html; charset=iso-8859-1"))
				{
					throw new HttpException(500, NULL, "Failed to send activation mail to '$mail'!");
				}
			}
			if (!empty($_POST["password"]))
			{
				$pw = hash("sha256", $_POST["password"]);

				$db_query = "UPDATE $db_table_users Set pw = '$pw' WHERE id = UNHEX('$id')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					throw new HttpException(500, NULL, "Failed to set user password.");
				}
				if (mysql_affected_rows($db_connection) != 1)
				{
					throw new HttpException(404, NULL, "User with this ID was not found.");
				}
			}
			header("HTTP/1.1 204 " . HttpException::getStatusMessage(204));
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