<?php
	require_once('config/constants.php');

	function secure_redirect()
	{
		if (!IS_SECURE)
		{
			header('Location: ' . SECURE_ROOT_URL . RELATIVE_URL);
			exit;
		}
	}
?>