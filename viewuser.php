<!DOCTYPE html>
<html>
	<?php
		$user = $_GET["user"];
	?>
	<head>
		<title><?php echo (empty($user)) ? "View users" : "User: $user"; ?></title>
	</head>
	<body>
		<?php
			include("db.php");
			$db_connection = db_ensureConnection();

			if (empty($user))
			{
				# list of users
			}
			else
			{
				# user profile
			}
		?>
	</body>
</html>