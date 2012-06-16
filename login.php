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
			<?php	if ($mode == "activate") { ?>
						<input type="hidden" name="name" value="<?php echo $_GET["name"]; ?>"/>
			<?php 	} else { ?>
							<tr>
								<td>Nickname:</td>
								<td><input type="text" name="name"/></td>
							</tr>
			<?php		if ($mode == "register") {	?>
							<tr>
								<td>Email:</td>
								<td><input type="text" name="mail"/></td>
							</tr>
				<?php
						}
					}
				?>
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
					if (isset($_POST["name"]) && isset($_POST["password"]))
					{
						require("db.php");
						$db_connection = db_ensureConnection();

						$name = $_POST["name"]; $pw = hash("sha256", $_POST["password"]);
						$escaped_name = mysql_real_escape_string($name, $db_connection);

						if ($mode == "register" && isset($_POST["mail"]))
						{
							$mail = $_POST["mail"];
							$escaped_mail = mysql_real_escape_string($mail, $db_connection);

							$token = mt_rand();
							date_default_timezone_set("UTC");
							$joined = date("Y-m-d");

							# check if already registered
							$db_query = "SELECT name FROM $db_table_users WHERE mail = '$escaped_mail' OR name = '$escaped_name'";
							$db_result = mysql_query($db_query, $db_connection)
							or die ("Failed to query for existing user.");

							if (mysql_num_rows($db_result) > 0)
							{
								die ("User with this nickname or email is already registered.");
							}

							# register
							$db_query = "INSERT INTO $db_table_users (name, mail, pw, activationToken, joined) VALUES ('$escaped_name', '$escaped_mail', '$pw', '$token', '$joined')";
							mysql_query($db_query, $db_connection)
							or die ("Failed to save new user: " . mysql_error());

							$url = "http://" . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'] . "?name=$name&mode=activate&token=$token";
							if (!mail($mail,
								"Confirm your registration to ALD",
								"To activate your account, go to <a href='$url'>$url</a>.",
								"FROM: noreply@{$_SERVER['HTTP_HOST']}\r\nContent-type: text/html; charset=iso-8859-1"))
							{
								die("Failed to send mail to '$mail'!");
							}
							echo "An activation mail was sent to the supplied mail address. Open the included link to activate your account.";
						}
						else if ($mode == "activate")
						{
							if (isset($_GET["token"]))
							{
								$token = mysql_real_escape_string($_GET["token"]);

								$db_query = "SELECT activationToken FROM $db_table_users WHERE name = '$escaped_name' AND activationToken = '$token' AND pw = '$pw'";
								$db_result = mysql_query($db_query, $db_connection)
								or die("Failed to query user database: " . mysql_error());

								if (mysql_num_rows($db_result) != 1)
								{
									die("User account with that name, password and token could not be found.");
								}

								$db_query = "UPDATE $db_table_users Set activationToken = '' WHERE name = '$escaped_name' AND activationToken = '$token' AND pw = '$pw'";
								mysql_query($db_query, $db_connection)
								or die("Failed to reset activation token.");

								echo "Your account was successfully activated.";
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