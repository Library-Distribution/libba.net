<div id="header">
	<div id="navigation">
		<img alt="libba.net logo" src="images/logo.png" id="header-logo"/>
		<div id="site-name">libba.net</div>

		<a class="nav-link" href="index">Home</a>
		<a class="nav-link" href="items">Code</a>
		<a class="nav-link" href="users">Users</a>
		<a class="nav-link" href="help">Help</a>
		<a class="nav-link" href="upload">Upload</a>
	</div>

	<div id="header-login">
	<?php
		$query_start = strpos($_SERVER["REQUEST_URI"], "?");
		$pure_url = substr($_SERVER["REQUEST_URI"], 0, $query_start);
		$redirect = in_array($pure_url, array("/login", "/logout", "/register", "/activate"))
						? "index"
						: urlencode($_SERVER["REQUEST_URI"]);

		if (isset($_SESSION["user"]))
		{
			echo "Welcome<br/><a href=\"users/{$_SESSION["user"]}/profile\">{$_SESSION["user"]}</a>!<hr/><a href=\"logout?redirect=$redirect\">Logout</a>";
		}
		else
		{
			echo "Welcome!<hr/><a href=\"login?redirect=$redirect\">Login</a><hr/><a href=\"register?redirect=$redirect\">Register</a>";
		}
	?>
	</div>

	<a href="http://l.autohotkey.net" title="Created for AutoHotkey"><img alt="AHK" src="images/AutoHotkey.png" id="header-ahk"/></a>
</div>