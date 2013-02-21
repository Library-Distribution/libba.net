<?php
	require_once(dirname(__FILE__) . '/../config/constants.php');
	function get_subfolder_level()
	{
		$sub_path = str_replace('?' . $_SERVER['QUERY_STRING'], '', RELATIVE_URL);
		return substr_count($sub_path, '/');
	}
	function get_subfolder_prefix()
	{
		$prefix = '';
		$level = get_subfolder_level();
		for ($i = 1; $i <= $level; $i++)
			$prefix .= '../';
		return $prefix;
	}
?>