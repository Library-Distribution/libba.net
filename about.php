<?php
	session_start();
	$page_title = "About ALD";
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title">About</h1>
		<div id="page-content">
			<p>
				This website is a draft for a library distribution system for <a href="http://www.autohotkey.com">AutoHotkey</a> (or <a href="http://l.autohotkey.net">AutoHotkey_L</a>, to be specific), similar to <a href="http://rubygems.org/">RubyGems</a>.
				It's entirely written by me, maul.esel, and has been tested and developed on <a href="http://www.apachefriends.org/de/xampp.html">XAMPP</a> on Windows.
			</p>
			<p>
				For better visual presentation, <a href="http://andrewpaglinawan.com/#quicksand">Andrew Paglinawan's &quot;Quicksand&quot; font</a> is used. It is licensed under the <a href="font/license.txt">SIL Open Font License, Version 1.1</a>.
				Many thanks for this wonderful work!
			</p>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>