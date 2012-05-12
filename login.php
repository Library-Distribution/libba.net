<!DOCTYPE html>
<html>
	<head>
	</head>
	<body>
		<?php
			$redirect = urldecode((empty($_GET["redirect")) ? "index.php" : $_GET["redirect"]);
			# todo...
			header("Location: $redirect");
		?>
	</body>
</html>