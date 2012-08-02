<div id="header">
	<div id="navigation">
		<img alt="ALD logo" src="images/logo.png" id="header-logo"/>
		<div id="site-name">ALD</div>

		<a class="nav-link" href="index">Home</a>
		<a class="nav-link" href="items">Code</a>
		<a class="nav-link" href="users">Users</a>
		<a class="nav-link" href="help">Help</a>
		<a class="nav-link" href="upload">Upload</a>
	</div>

	<div id="header-login">
	<?php
		$redirect = (str_replace("?" . $_SERVER["QUERY_STRING"], "", $_SERVER["REQUEST_URI"]) == "/login")
						? "index"
						: urlencode($_SERVER["REQUEST_URI"]);
		if (isset($_SESSION["user"]))
		{
			echo "Welcome<br/><a href=\"users/{$_SESSION["user"]}/profile\">{$_SESSION["user"]}</a>!<hr/><a href=\"login?mode=logout&amp;redirect=$redirect\">Logout</a>";
		}
		else
		{
			echo "Welcome!<hr/><a href=\"login?mode=login&amp;redirect=$redirect\">Login</a><hr/><a href=\"login?mode=register&amp:redirect=$redirect\">Register</a>";
		}
	?>
	</div>

	<a href="http://l.autohotkey.net" title="Created for AutoHotkey"><img alt="AHK" src="images/AutoHotkey.png" id="header-ahk"/></a>
</div>