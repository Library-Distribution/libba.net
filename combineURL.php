<?php
	function combineURL($url, $params)
	{
		$url .= (strpos($url, '?') !== FALSE && substr($url, -1) != '?' ? '&' : '?');
		$first= true;
		foreach ($params AS $param => $value)
		{
			$url .= ($first ? '' : '&') . "$param=$value";
			$first = false;
		}
		return $url;
	}
?>