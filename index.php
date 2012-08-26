<?php
	session_start();
	$page_title = "ALD - AutoHotkey Library Distribution system";
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/index.css"/>
	</head>
	<body>
		<?php require("header.php"); ?>
		<!--div id="page-content"-->
			<div id="vert1"></div><div id="vert2"></div>
			<div id="horz1"></div><div id="horz2"></div>

			<a href="about" title="Get to know the AutoHotkey Library Distribution"><img id="logo" alt="ALD-logo" src="images/logo.png"/></a>

			<a href="https://github.com/maul-esel/ALD" title="Developed on github"><img alt="github" src="images/github.png" id="github"/></a>
			<a href="http://trello.com" title="Planned on trello"><img alt="trello" src="images/trello.png" id="trello"/></a>

			<a href="http://php.net" title="Written in PHP"><img src="http://www.php.net/images/logos/php-med-trans.png" alt="php" id="php"/></a>
			<a href="http://mysql.com" title="Storing data using MySQL"><img alt="MySQL" src="http://upload.wikimedia.org/wikipedia/de/1/1f/Logo_MySQL.svg" id="MySQL"/></a>
			<a href="http://apache.org" title="Running on Apache"><img alt="apache" src="http://www.apache.org/images/feather-small.gif" id="apache"/></a>

			<h1>ALD &mdash;<br/>AutoHotkey Library Distribution system</h1>

			<div id="short1">Share your AutoHotkey work &mdash; in an easy and comfortable way</div>
			<div id="short2">Get all the libraries you need &mdash; fast, reliable and always up-to-date</div>

			<div id="long1">Uploading your work is as simple as 1-2-3. Your code is available to all users easily. You can make it into the standard library for AutoHotkey.</div>
			<div id="long2">Get a set of libraries for everyday use. Enhance AutoHotkey with specialized pieces of code. Code review and careful selection for the standard lib ensure you get always the best.</div>
		<!--/div-->
		<?php require("footer.php"); ?>
	</body>
</html>