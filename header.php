<div id="header">
	<div id="navigation">
		<div><img alt="ALD logo" src="images/logo.png" id="header-logo"/></div>
		<div id="site-name">ALD</div>

		<a class="nav-link" href="index">Home</a>
		<a class="nav-link" href="viewitem">Code</a>
		<a class="nav-link" href="viewuser">Users</a>
		<a class="nav-link" href="help">Help</a>
		<a class="nav-link" href="upload">Upload</a>

		<div id="header-login">
		<?php
			if (isset($_SESSION["user"]))
			{
				echo "Welcome<br/><a href=\"viewuser?user={$_SESSION["user"]}\">{$_SESSION["user"]}</a>!<hr/><a href=\"login?mode=logout\">Logout</a>";
			}
			else
			{
				echo "Welcome!<hr/><a href=\"login?mode=login\">Login</a><hr/><a href=\"login?mode=register\">Register</a>";
			}
		?>
		</div>

		<a href="http://l.autohotkey.net" title="Created for AutoHotkey"><img alt="AHK" src="images/AutoHotkey.png" id="header-ahk"/></a>
	</div>
</div>