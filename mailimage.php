<?php
	session_start();

	require_once('api/db.php');
	require_once('db2.php');
	$db_connection = db_ensure_connection();

	$db_query = "SELECT mail, show_mail FROM $db_table_user_profile WHERE id = UNHEX('" . mysql_real_escape_string($_GET['user']) . "')";
	$db_result = mysql_query($db_query, $db_connection);

	if (!$db_connection)
	{
		header('HTTP/1.1 500 Server error');
		exit;
	}

	if (mysql_num_rows($db_result) != 1)
	{
		header('HTTP/1.1 404 Not found');
		exit;
	}

	$user = mysql_fetch_assoc($db_result);
	if ($user['show_mail'] == 'hidden' || ($user['show_mail'] == 'members' && !isset($_SESSION['user'])))
	{
		header('HTTP/1.1 403 Forbidden');
		exit;
	}

	$width = 2 + 10 * strlen($user['mail']);

	if (!$image = @imagecreate($width, 15))
	{
		header('HTTP/1.1 500 Server error');
		exit;
	}

	
	$alpha = imagecolorallocatealpha($image, 255, 255, 255, 127);
	$black = imagecolorallocate($image, 0, 0, 0);	

	if (!imagestring($image, 5, 1, 1, $user['mail'], $black))
	{
		header('HTTP/1.1 500 Server error');
		exit;
	}

	header('Content-Type: image/jpeg');
	if (!imagepng($image))
	{
		header('HTTP/1.1 500 Server error');
		exit;
	}
	imagedestroy($image);
?>