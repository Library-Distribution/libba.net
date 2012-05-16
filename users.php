<?php
	function validateLogin($user, $pw)
	{
		global $db_table_users;
		$db_connection = db_ensureConnection();

		$pw = hash("sha256", $pw);
		$escaped_user = mysql_real_escape_string($user, $db_connection);

		$db_query = "SELECT pw, activationToken FROM $db_table_users WHERE name = '$escaped_user'";
		$db_result = mysql_query($db_query, $db_connection)
		or die ("Could not find the specified user name.");

		$data = mysql_fetch_object($db_result);
		if ($data->activationToken)
		{
			die ("Account is currently deactivated.");
		}
		if ($data->pw != $pw)
		{
			die ("Invalid password was specified.");
		}
	}
?>