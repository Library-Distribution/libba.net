<?php
	define('IS_LOCALHOST', $_SERVER["SERVER_ADDR"] == "127.0.0.1");
	define('IS_HTTPS', !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off");
	define('IS_SECURE', IS_HTTPS || IS_LOCALHOST);

	define('REL_ADDRESS', substr(dirname(dirname(__FILE__)), strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/'))) . '/');

	define('ROOT_URL', (IS_HTTPS ? "https" : "http") . "://"
						. $_SERVER["SERVER_NAME"]
						. REL_ADDRESS);
	define('SECURE_ROOT_URL', IS_SECURE ? ROOT_URL : "https://ahk4.net/user/maulesel/");

	define('API_URL', ROOT_URL . "api");
	define('SECURE_API_URL', SECURE_ROOT_URL . "api");
?>