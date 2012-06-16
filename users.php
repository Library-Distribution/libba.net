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

	function user_get_nick($id)
	{
		global $db_table_users;
		$db_connection = db_ensureConnection();

		$db_query = "SELECT name FROM $db_table_users WHERE id = UNHEX('" . mysql_real_escape_string($id) . "')";
		$db_result = mysql_query($db_query, $db_connection)
		or die ("Could not find the user name.");

		while ($data = mysql_fetch_object($db_result))
		{
			return $data->name;
		}
	}

	function user_get_id_by_nick($nick)
	{
		global $db_table_users;
		$db_connection = db_ensureConnection();

		$db_query = "SELECT HEX(id) FROM $db_table_users WHERE name = '" . mysql_real_escape_string($nick) . "'";
		$db_result = mysql_query($db_query, $db_connection)
		or die ("Could not find the user ID.");

		while ($data = mysql_fetch_assoc($db_result))
		{
			return $data['HEX(id)'];
		}
	}
?>