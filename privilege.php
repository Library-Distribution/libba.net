<?php
	define('PRIVILEGE_NONE', 0);
	define('PRIVILEGE_USER_MANAGE', 2);
	define('PRIVILEGE_REVIEW', 4);
	define('PRIVILEGE_STDLIB', 8);
	define('PRIVILEGE_ADMIN', 16);

	function hasPrivilege($comb, $priv)
	{
		return ($comb & $priv) == $priv;
	}

	function hasExtendedPrivileges($comb) {
		return $comb != PRIVILEGE_NONE;
	}
?>