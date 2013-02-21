<?php
	require_once('util/ALD.php');
	require_once('util/db.php');
	require_once('modules/HttpException/HttpException.php');
	require_once('config/constants.php');

	try
	{
		# check if required data present
		if (empty($_POST['user']) || empty($_POST['mail']) || empty($_POST['id']))
		{
			throw new HttpException(400, NULL, 'data missing');
		}

		$db_connection = db_ensure_connection();

		# check if account exists
		$db_query = 'SELECT * FROM ' . DB_TABLE_USER_PROFILE . ' WHERE mail = "' . $mail . '" OR id = UNHEX("' . $id . '")';
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500, NULL, mysql_error());
		}
		if (mysql_num_rows($db_result) > 0)
		{
			throw new HttpException(409, NULL, 'Account already exists');
		}

		# check if user is correct
		$api = new ALD( API_URL );
		try
		{
			$user = $api->getUserById($_POST['id']);
		}
		catch (HttpException $e)
		{
			throw new HttpException(404, NULL, 'User not found in backend: "' . $e->getMessage() . '")';
		}
		if ($user['name'] != $_POST['user'] || $user['mail'] != md5($_POST['mail']))
		{
			throw new HttpException(400, NULL, 'data invalid');
		}

		# create account
		$id = mysql_real_escape_string($_POST['id']);
		$mail = mysql_real_escape_string($_POST['mail']);
		
		$db_query = 'INSERT INTO ' . DB_TABLE_USER_PROFILE . ' (id, mail) VALUES (UNHEX("' . $id . '"), "' . $mail . '")';
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			throw new HttpException(500, NULL, mysql_error());
		}

		header('HTTP/1.1 204 ' . HttpException::getStatusMessage(204));
		exit;
	}
	catch (HttpException $e)
	{
		header('HTTP/1.1 ' . $e->getCode() . ' ' . HttpException::getStatusMessage($e->getCode()));
		echo $e->getMessage();
	}
?>