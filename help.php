<?php
	session_start();
	$page_title = "Help | ALD";
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
	</head>
	<body>
		<h1 id="page-title">ALD help page</h1>
		<div id="page-content">
			<h2 class="question">What is this?</h2>
			<div class="answer">
				This is a draft for a webpage backend for a standardized AutoHotkey library distribution system.
				Users can <a href="login.php?mode=register">register</a> (really simple) to upload their libraries and applications written in AutoHotkey here.
				Anyone can download them in a standardized way and use them.
			</div>
			<h2 class="question">How can I use the software provided here?</h2>
			<div class="answer">
				<p>
					You can download and install the software provided here using <a href="https://github.com/maul-esel/marmoreal">marmoreal</a>.
					You can run it through command line or user interface. It will download the packages, detect information about them, and install them to your library folder or in a custom place for apps.
				</p>
				<p>
					However, marmoreal is not yet finished (at all). In the meantime, click the download link on the specific page to download a *.zip file.
					You can unpack it and extract the included files. The <cite>definition.ald</cite> file is for internal use by marmoreal and this website only.
				</p>
			</div>
			<h2 class="question">How can I upload my stuff?</h2>
			<div class="answer">
				<p>
					As well as for downloading, you can use <a href="https://github.com/maul-esel/marmoreal">marmoreal</a>.
					Define a new package, fill in the fields in the UI and add the required files.
					A ZIP-package is then created, which can either be uploaded directly by marmoreal or <a href="upload.php">on this site</a>.
				</p>
				<p>
					Until marmoreal is really capable of doing so, you must create the package yourself.
					It's only a ZIP file containing the required files plus a XML file called <cite>definition.ald</cite>.
					It must be valid according to <a href="schema/2012/package.xsd">this XSD schema</a>.
				</p>
			</div>
			<h2 class="question">?</h2>
			<div class="answer"></div>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>