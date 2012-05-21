<?php
	require_once("db.php");
	require_once("HttpException.php");

	function validateLogin($user, $pw)
	{
		global $db_table_users;
		$db_connection = db_ensureConnection();

		$pw = hash("sha256", $pw);
		$escaped_user = mysql_real_escape_string($user, $db_connection);

		$db_query = "SELECT pw, activationToken FROM $db_table_users WHERE nick = '$escaped_user'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		$data = mysql_fetch_object($db_result);
		if ($data->activationToken)
		{
			throw new HttpException(403, NULL, "Account is currently deactivated.");
		}
		if ($data->pw != $pw)
		{
			throw new HttpException(403, NULL, "Invalid credentials were specified.");
		}
	}

	function user_basic_auth($realm)
	{
		if (empty($_SERVER["PHP_AUTH_USER"]) || empty($_SERVER["PHP_AUTH_PW"]))
		{
			throw new HttpException(401, array("WWW-Authenticate" => "Basic realm=\"$realm\""));
		}
		validateLogin($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);
	}
?>