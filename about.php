<?php
	session_start();
	$page_title = "About";
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
	</head>
	<body class="pretty-ui">
		<h1 id="page-title">About</h1>
		<div id="page-content">
			<p>
				This website is a draft for a library distribution system for <a href="http://www.autohotkey.com">AutoHotkey</a> (or <a href="http://l.autohotkey.net">AutoHotkey_L</a>, to be specific), similar to <a href="http://rubygems.org/">RubyGems</a>.
				It's entirely written by me, maul.esel, and has been tested and developed on <a href="http://www.apachefriends.org/de/xampp.html">XAMPP</a> on Windows / Ubuntu.
			</p>
			<p>
				For better visual presentation, <a href="http://andrewpaglinawan.com/#quicksand">Andrew Paglinawan's &quot;Quicksand&quot; font</a> is used. It is licensed under the <a href="font/license.txt">SIL Open Font License, Version 1.1</a>.
				Many thanks for this wonderful work!
			</p>
			<p>
				The icons used throughout the site are taken from the <em>GLYPHICONS FREE</em> icon set by Jan Kovařík. It is licensed under the <a href="images/CC%20BY%203.txt">Creative Commons Attribution 3.0 Unported</a> license and available at <a href="http://glyphicons.com">glyphicons.com</a>.
				Thanks for these wonderful icons!
			</p>
			<p>
				libba.net makes use of 3rd party software written in PHP, CSS and Javascript:
				<ul>
					<li>Thanks to <a href="http://michelf.ca">Michel Fortin</a> for <a href="http://michelf.ca/projects/php-markdown/">PHP Markdown Extra</a> (<a href="markdown/License.txt">license</a>) and <a href="http://michelf.ca/projects/php-smartypants/">PHP SmartyPants</a> (<a href="smartypants/License.txt">license</a>).</li>
					<li>Thanks to <a href="http://ezyang.com/">Edward Z. Yang</a> for <a href="http://htmlpurifier.org">HTMLPurifier</a> (<a href="htmlpurifier/LICENSE">license</a>).</li>
					<li>Thanks to <a href="https://github.com/lojjic">Jason Johnston</a>  for <a href="http://css3pie.com/">CSS3 PIE</a> (<a href="https://raw.github.com/lojjic/PIE/master/LICENSE-APACHE2.txt">license</a>).</li>
					<hr/>
					<li>As jor Javascript, I wish to thank the <a href="http://jquery.com">jQuery</a> and <a href="http://http://jqueryui.com/">jQuery-UI</a> teams (<a href="https://raw.github.com/jquery/jquery/master/MIT-LICENSE.txt">license</a>).</li>
					<li>Also a big thanks to the <a href="http://modernizr.js">modernizr.js</a> team (modernizr.js is <a href="https://github.com/Modernizr/Modernizr/blob/master/readme.md">licensed under the MIT license</a>).
				</ul>
			</p>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>