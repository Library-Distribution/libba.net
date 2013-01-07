<div id="user-navigation">
<?php
	require_once("../privilege.php");

	$possible_modes = array("profile" => "Profile",
							"activity" => "Activity",
							"items" => "Libs &amp; Apps",
							"achievements" => "Achievements",
							"modify" => "Change settings",
							"suspend" => "Suspend user");
	foreach ($possible_modes AS $mode => $name)
	{
		$class = ($mode == $current_mode) ? "class=\"nav-current nav-url\"" : "";
		$style = ($mode == "modify" && (!$logged_in || $_SESSION["user"] != $user)) || ($mode == "suspend" && (!$logged_in || !hasPrivilege($_SESSION["privileges"], PRIVILEGE_USER_MANAGE) || $user == $_SESSION["user"]))
				? "style=\"display: none\""
				: "";
		echo "<a href=\"./$mode\" $class title='$mode' $style><div>$name</div></a>";
	}
?>
</div>