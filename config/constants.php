<?php
	define('REL_ADDRESS', substr(dirname(dirname(__FILE__)), strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/'))) . '/');
	define('ROOT_URL', ($_SERVER["HTTPS"] ? "https" : "http") . "://"
						. $_SERVER["SERVER_NAME"]
						. REL_ADDRESS);
?>