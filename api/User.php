<?php
require_once("db.php");
require_once("HttpException.php");

class User
{
	const PRIVILEGE_NONE = 0;
	const PRIVILEGE_USER_MANAGE = 2;
	const PRIVILEGE_REVIEW = 4;
	const PRIVILEGE_DEFAULT_INCLUDE = 8;
	const PRIVILEGE_ADMIN = 16;

	public static function hasPrivilegeById($id, $privilege)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT privileges FROM $db_table_users WHERE id = UNHEX('" . mysql_real_escape_string($id, $db_connection) . "')";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		if (mysql_num_rows($db_result) != 1)
		{
			throw new HttpException(404, NULL, "User not found");
		}

		$data = mysql_fetch_object($db_result);
		return (((int)$data->privileges) & $privilege) == $privilege;
	}

	public static function hasPrivilege($name, $privilege)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT privileges FROM $db_table_users WHERE name = '" .  mysql_real_escape_string($name, $db_connection) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		if (mysql_num_rows($db_result) != 1)
		{
			throw new HttpException(404, NULL, "User not found");
		}

		$data = mysql_fetch_object($db_result);
		return (((int)$data->privileges) & $privilege) == $privilege;
	}

	public static function existsName($name)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT id FROM $db_table_users WHERE name = '" . mysql_real_escape_string($name, $db_connection) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}
		return mysql_num_rows($db_result) == 1;
	}

	public static function existsMail($mail)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT id FROM $db_table_users WHERE mail = '" . mysql_real_escape_string($mail, $db_connection) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}
		return mysql_num_rows($db_result) == 1;
	}

	public static function validateLogin($user, $pw, $throw = true)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$pw = hash("sha256", $pw);
		$escaped_user = mysql_real_escape_string($user, $db_connection);

		$db_query = "SELECT pw, activationToken FROM $db_table_users WHERE name = '$escaped_user'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			if (!$throw)
			{
				return false;
			}
			throw new HttpException(500);
		}

		if (mysql_num_rows($db_result) != 1)
		{
			if (!$throw)
			{
				return false;
			}
			throw new HttpException(403, NULL, "User not found");
		}

		$data = mysql_fetch_object($db_result);
		if ($data->activationToken)
		{
			if (!$throw)
			{
				return false;
			}
			throw new HttpException(403, NULL, "Account is currently deactivated.");
		}
		if ($data->pw != $pw)
		{
			if (!$throw)
			{
				return false;
			}
			throw new HttpException(403, NULL, "Invalid credentials were specified.");
		}
		return true;
	}

	public static function getName($id)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT name FROM $db_table_users WHERE id = UNHEX('" . mysql_real_escape_string($id, $db_connection) . "')";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_object($db_result))
		{
			return $data->name;
		}
		throw new HttpException(404, NULL, "User not found");
	}

	public static function getID($name)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT HEX(id) FROM $db_table_users WHERE name = '" . mysql_real_escape_string($name, $db_connection) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_assoc($db_result))
		{
			return $data["HEX(id)"];
		}
		throw new HttpException(404, NULL, "User not found");
	}

	public static function getToken($name)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT activationToken FROM $db_table_users WHERE name = '" . mysql_real_escape_string($name, $db_connection) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_object($db_result))
		{
			return $data->activationToken;
		}
		throw new HttpException(404, NULL, "User not found");
	}

	public static function setToken($name, $token)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "UPDATE $db_table_users  Set activationToken = '" . mysql_real_escape_string($token, $db_connection) . "' WHERE name = '" . mysql_real_escape_string($name, $db_connection) . "'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_object($db_result))
		{
			return $data->activationToken;
		}
		throw new HttpException(404, NULL, "User not found");
	}

	public static function getPrivileges($id)
	{
		global $db_table_users;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT privileges FROM $db_table_users WHERE id = UNHEX('" . mysql_real_escape_string($id, $db_connection) . "')";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500);
		}

		while ($data = mysql_fetch_assoc($db_result))
		{
			return $data["privileges"];
		}
		throw new HttpException(404, NULL, "User not found");
	}
}
?>