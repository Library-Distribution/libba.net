<!DOCTYPE html>
<html>
	<head>
	</head>
	<body>
		<?php
			$redirect = (empty($_GET["redirect")) ? "index.php" : urldecode($_GET["redirect"]);
			# todo...
			header("Location: $redirect");
		?>
	</body>
</html>