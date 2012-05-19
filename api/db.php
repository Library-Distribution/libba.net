<?php
	require_once("HttpException.php");

	# database tables
	$db_table_main = "data";
	$db_table_users = "users";

	function db_ensure_connection()
	{
		# settings for database
		static $db_server = "localhost";
		static $connection = false;

		require("db_cred.php"); # contains variable declarations for $db_pw, $db_user and $db_main

		if (!$connection)
		{
			$connection = mysql_connect($db_server, $db_user, $db_pw);
			if (!$connection)
			{
				throw new HttpException(500);
			}
			if (!mysql_select_db($db_main, $connection))
			{
				throw new HttpException(500);
			}
		}
		return $connection;
	}
?>