<?php
	session_start();

	if (!isset($_SESSION["user"]))
	{
		header("Location: login?mode=login&redirect=" . urlencode($_SERVER["REQUEST_URI"]));
		exit;
	}
	$page_title = "moderator control panel";
?>
<html>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
	</head>
	<body>
		<?php
			require("header.php");
		?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			Work in progress!
		</div>
		<?php
			require("footer.php");
		?>
	</body>
</html>