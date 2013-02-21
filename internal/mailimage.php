<?php
	session_start();

	require_once('../util/db.php');
	$db_connection = db_ensure_connection();

	$db_query = 'SELECT mail, show_mail FROM ' . DB_TABLE_USER_PROFILE . ' WHERE id = UNHEX("' . mysql_real_escape_string($_GET['user']) . '")';
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

	$width = 10 + 7.5 * strlen($user['mail']);

	if (!$image = @imagecreate($width, 15))
	{
		header('HTTP/1.1 500 Server error');
		exit;
	}

	
	$alpha = imagecolorallocatealpha($image, 255, 255, 255, 127);
	$black = imagecolorallocate($image, 0, 0, 0);

	if (!imagettftext($image, 10, 0, 5, 12.5, $black, '../style/font/Quicksand_Bold-webfont.ttf', $user['mail']))
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