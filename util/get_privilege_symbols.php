<?php
	require_once('privilege.php');

	function get_privilege_symbols($priv) {
		$map = array(PRIVILEGE_USER_MANAGE => array('title' => 'user moderators', 'team' => 'moderators', 'image' => 'moderator.png'),
					PRIVILEGE_REVIEW => array('title' => 'code review team', 'team' => 'review', 'image' => 'review.png'),
					PRIVILEGE_STDLIB => array('title' => 'stdlib team', 'team' => 'stdlib', 'image' => 'achievements/stdlib.png'),
					PRIVILEGE_ADMIN => array('title' => 'admins', 'team' => 'admins', 'image' => 'admin.png'));

		$content = '<div class="privilege-symbols">';
		if (hasExtendedPrivileges($priv)) {
			foreach ($map AS $privilege => $data) {
				if (hasPrivilege($priv, $privilege)) {
					$content .= '<a title="' . $data['title'] . '" href="users?team=' . $data['team'] . '"><img src="images/' . $data['image'] . '" alt="@"/></a>';
				}
			}
		}
		$content .= '</div>';
		return $content;
	}
?>