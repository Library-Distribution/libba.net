<div id="user-navigation">
<?php
	require_once('../privilege.php');

	$possible_modes = array('profile' => 'Profile',
						'activity' => 'Activity',
						'items' => 'Libs &amp; Apps',
						'achievements' => 'Achievements',
						'modify' => 'Change settings',
						'suspend' => 'Suspend user');
	foreach ($possible_modes AS $mode => $name)
	{
		$id = ($mode == $current_mode) ? 'id="nav-current"' : '';
		$style = ($mode == 'modify' && (!$logged_in || $_SESSION['user'] != $user)) || ($mode == 'suspend' && (!$logged_in || !hasPrivilege($_SESSION['privileges'], PRIVILEGE_USER_MANAGE) || $user == $_SESSION['user']))
				? 'style="display: none"'
				: '';
		echo "<a href='./$mode' $id $style><div>$name</div></a>";
	}
?>
</div>