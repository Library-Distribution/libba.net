<?php
	function validateLogin($user_name, $user_pw)
	{
		global $db_table_users;
		$db_connection = db_ensureConnection();

		$user_pw = hash("sha256", $user_pw);
		$escaped_user = mysql_real_escape_string($user_name, $db_connection);

		$db_query = "SELECT pw FROM $db_table_users WHERE name = '$escaped_user'";
		$db_result = mysql_query($db_query, $db_connection)
		or die ("Could not find the specified user name.");

		$pw = mysql_fetch_object($db_result)->pw;
		if ($pw != $user_pw)
		{
			die ("Invalid password was specified.");
		}
	}
?>