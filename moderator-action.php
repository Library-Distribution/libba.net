<?php
	session_start();

	require_once('api/HttpException.php');
	require_once('ALD.php');
	require_once('config/constants.php');
	require_once('api/User.php');
	require_once('privilege.php');

	if (!isset($_GET['action']))
	{
		throw new HttpException(400);
	}
	$action = strtolower($_GET['action']);
	if (isset($_GET['redirect']))
	{
		$redirect = urldecode($_GET['redirect']);
	}
	$return_error = isset($_GET['return_error']) && $_GET['return_error'] && strtolower($_GET['return_error']) != 'false' && isset($redirect);

	try
	{
		if ($action == 'suspend')
		{
			################ authentication ################
			if (empty($_SESSION['user']) || empty($_SESSION['password']))
			{
				throw new HttpException(401, array('WWW-Authenticate' => 'Basic realm="Restricted actions"'));
			}
			User::validateLogin($_SESSION['user'], $_SESSION['password']);
			##########################################

			if (!hasPrivilege($_SESSION['privileges'], PRIVILEGE_USER_MANAGE))
			{
				throw new HttpException(403);
			}
			if (!isset($_GET['value']) || !in_array((int)$_GET['value'], array(1, -1)) || !isset($_GET['user']))
			{
				throw new HttpException(400);
			}

			User::setToken($_GET['user'], (int)$_GET['value'] > 0 ? mt_rand() : '');
		}
		else if ($action == 'review')
		{
			$api = new ALD( SECURE_API_URL );
			$api->modifyItemById($_GET['id'], $_SESSION['user'], $_SESSION['password'], $_GET['value']);
		}
		else if ($action == 'default')
		{
			$api = new ALD( SECURE_API_URL );
			$api->modifyItemById($_GET['id'], $_SESSION['user'], $_SESSION['password'], NULL, $_GET['value']);
		}
		else
		{
			throw new HttpException(400);
		}
	}
	catch (HttpException $e)
	{
		if (isset($redirect))
		{
			if ($return_error)
			{
				$redirect .= (strpos($redirect, '?') && substr($redirect, -1) != '?' ? '&' : '?') . "error={$e->getCode()}";
			}
			header("Location: $redirect");
			#echo $e->getCode() . " - " . $e->getMessage() . " - " . $redirect;
		}
		else
		{
			header("HTTP/1.1 {$e->getCode()} " . HttpException::getStatusMessage($e->getCode()));
			#echo $e->getMessage();
		}
		exit;
	}

	if (isset($redirect))
	{
		header("Location: $redirect");
		#echo "SUCCESS - " . $redirect;
	}
	else
	{
		header('HTTP/1.1 204 ' . HttpException::getStatusMessage(204));
		#echo "SUCCESS";
	}
	exit;
?>