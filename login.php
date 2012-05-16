<!DOCTYPE html>
<html>
	<?php
		$redirect = (empty($_GET["redirect"])) ? "index.php" : urldecode($_GET["redirect"]);
		$mode = isset($_GET["mode"]) ? $_GET["mode"] : "register"; # maybe change default later
		if ($mode == "login")
		{
			$page_title = "Login";
		}
		else if ($mode == "register")
		{
			$page_title = "Register";
		}
		else if ($mode == "activate")
		{
			$page_title = "Activate account";
		}
	?>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (empty($_POST))
				{
			?>
					<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
						<table>
			<?php	if ($mode == "activate")
					{
			?>				<input type="hidden" name="mail" value="<?php echo $_GET["mail"]; ?>"/>				<?php
					}
					else
					{
			?>				<tr>
								<td>Email:</td>
								<td><input type="text" name="mail"/></td>
							</tr>
			<?php } ?>
							<tr>
								<td>Password:</td>
								<td><input type="password" name="password"/></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="<?php echo ($mode == "login") ? "Login" : "Register"; ?>"/></td>
							</tr>
						</table>
					</form>
			<?php
				}
				else
				{
					if (isset($_POST["mail"]) && isset($_POST["password"]))
					{
						require("db.php");
						$db_connection = db_ensureConnection();

						$mail = $_POST["mail"]; $pw = hash("sha256", $_POST["password"]);
						$escaped_mail = mysql_real_escape_string($mail, $db_connection);

						if ($mode == "register")
						{
							$token = mt_rand();
							date_default_timezone_set("UTC");
							$joined = date("Y-m-d");

							# check if already registered
							$db_query = "SELECT name FROM $db_table_users WHERE name = '$escaped_mail'";
							$db_result = mysql_query($db_query, $db_connection)
							or die ("Failed to query for existing user.");

							if (mysql_num_rows($db_result) > 0)
							{
								die ("User with this email is already registered.");
							}

							# register
							$db_query = "INSERT INTO $db_table_users (name, pw, activationToken, joined) VALUES ('$escaped_mail', '$pw', '$token', '$joined')";
							mysql_query($db_query, $db_connection)
							or die ("Failed to save new user: " . mysql_error());

							if (!mail($mail,
								"Confirm your registration to ALD",
								"To activate your account, go to '" . __FILE__ . "?mail=$mail&mode=activate&token=$token'.",
								"FROM: noreply@maulesel.ahk4.me"))
							{
								die("Failed to send mail to '$mail'!");
							}
							echo "An activation mail was sent to the supplied mail address. Open the included link to activate your account.";
						}
						else if ($mode == "activate")
						{
							if (isset($_GET["token"]))
							{
								$token = $_GET["token"];

								$db_query = "SELECT token FROM $db_table_users WHERE name = '$escaped_mail' AND activationToken = '$token' AND pw = '$pw'";
								$db_result = mysql_query($db_query, $db_connection)
								or die("Failed to query user database: " . mysql_error());

								if (mysql_num_rows($db_result) != 1)
								{
									die("User account with that mail, password and token could not be found.");
								}

								$db_query = "UPDATE $db_table_users Set activationToken = '' WHERE name = '$mail' AND activationToken = '$token' AND pw = '$pw'";
								mysql_query($db_query, $db_connection)
								or die("Failed to reset activation token.");
							}
						}
						else if ($mode == "login")
						{
							# ...
							echo "NOT YET IMPLEMENTED / NOT REQUIRED!";
						}
					}
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>