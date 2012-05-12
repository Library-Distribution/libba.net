<?php
	# database tables
	$db_table_main = "data";
	$db_table_users = "users";

	function db_ensureConnection()
	{
		# settings for database
		static $db_server = "localhost", $db_user = "root";
		static $db_main = "adl_test";
		require("db_pw.php"); # contains a variable declaration: 'static $db_pw = "password"'

		static $connection = false;
		if (!$connection)
		{
			$connection = mysql_connect($db_server, $db_user, $db_pw)
			or die ("Could not connect to database server.");
			mysql_select_db($db_main, $connection)
			or die ("Could not select database!");
		}
		return $connection;
	}
?>