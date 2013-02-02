<?php
	session_start();
	$page_title = "Home";
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("partials/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/index.css"/>
	</head>
	<body>
		<?php require("partials/header.php"); ?>
		<div id="page-content">
			<a href="about" title="Get to know libba.net"><img id="logo" alt="libba.net logo" src="images/logo.png"/></a>

			<h1>libba.net<br/>&mdash;<br/>AutoHotkey Library Distribution system</h1>
			<div id="give">
				<h3>Share your AutoHotkey work<br/>&mdash;<br/>in an easy and comfortable way</h3><hr/>
				<p>
					Uploading your work is as simple as 1-2-3 using the tool provided for packaging and uploading. Your code is available for download and installation to all users easily via this website, the marmoreal command line or the marmoreal user interface.
					If you upload helpful and interesting libraries, your code can even make it into the standard library for AutoHotkey - a set of libraries compiled for installation along with AutoHotkey itself.
					And you can automatically specify dependencies on which your library or app relies - marmoreal will install them together with your code automatically.
				</p>
			</div>
			<div id="get">
				<h3>Get all the libraries you need<br/>&mdash;<br/>fast, reliable and always up-to-date</h3><hr/>
				<p>
					Get a set of libraries and apps for everyday use, which have been carefully reviewed to ensure no malicious code is included. Manage them easily using the command line or a graphical user interface.
					Browse the available pieces of code on this website and see what others think of them. Find genius code which you may never have thought of and explore the manifold possible usages of AutoHotkey.
					Install a readily compiled set of the best libraries recommended with every AutoHotkey installation. Never think about dependencies again - they are managed automatically for you.
				</p>
			</div>

			<div id="logo-list">
				<a href="http://autohotkey.com" title="Created for AutoHotkey"><img alt="AHK" src="images/AutoHotkey.png"/></a>
				<a href="https://github.com/maul-esel/libba.net" title="Developed on github"><img alt="github" src="images/github.png"/></a>
				<a href="http://trello.com" title="Planned on trello"><img alt="trello" src="images/trello.png"/></a>
				<a href="http://php.net" title="Written in PHP"><img src="images/php.png" alt="php"/></a>
				<a href="http://mysql.com" title="Storing data using MySQL"><img alt="MySQL" src="images/MySQL.png"/></a>
				<a href="http://apache.org" title="Running on Apache"><img alt="apache" src="images/apache.gif"/></a>
			</div>
		</div>
		<?php require("partials/footer.php"); ?>
	</body>
</html>