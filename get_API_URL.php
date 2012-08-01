<?php
	function get_API_URL($secure = false)
	{
		static $site_subpath = "/user/maulesel";

		if ($secure)
		{
			return $_SERVER["SERVER_ADDR"] == "127.0.0.1" ? get_API_URL() : "https://ahk4.net$site_subpath/api";
		}
		else
		{
			return !empty($_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}$site_subpath/api" : "http://{$_SERVER["SERVER_NAME"]}/api";
		}
	}
?>