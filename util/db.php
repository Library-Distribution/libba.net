<?php
	require_once(dirname(__FILE__) . '/../api/HttpException.php');
	require_once(dirname(__FILE__) . '/../config/database.php'); # import database settings

	function db_ensure_connection()
	{
		static $connection = false;

		if (!$connection)
		{
			$connection = mysql_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
			if (!$connection)
			{
				throw new HttpException(500);
			}
			if (!mysql_select_db(DB_NAME, $connection))
			{
				throw new HttpException(500);
			}
		}
		return $connection;
	}

	function db_get_enum_column_values($table, $column, &$values)
	{
		$db_connection = db_ensure_connection();
		$db_query = "SHOW COLUMNS IN $table WHERE Field = '" . mysql_real_escape_String($column) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			return false;
		}
		$data = mysql_fetch_assoc($db_result);
		$values = explode("','",substr($data["Type"],6,-2));
		return true;
	}
?>