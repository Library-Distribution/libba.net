<?php
	session_start();
	date_default_timezone_set("UTC");

	$page_title = "";
	$mode = isset($_GET["mode"]) ? $_GET["mode"] : "register"; # maybe change default later
	$error = true;
	$redirect = (empty($_GET["redirect"])) ? "/index" : urldecode($_GET["redirect"]);
	$should_redirect = true;

	if (empty($_POST))
	{
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
		else if ($mode == "logout")
		{
			$page_title = "Logged out";
			unset($_SESSION["privileges"]); unset($_SESSION["user"]); unset($_SESSION["password"]); # unset here as session_destroy() seems to have no effect on currently loaded page
			session_destroy();
		}
		$should_redirect = false;
		$error = false;
	}
	else
	{
		if (isset($_POST["name"]) && isset($_POST["password"]))
		{
			for ($i = 0; $i < 1; $i++)
			{
				require_once("api/db.php");
				$db_connection = db_ensure_connection();

				$name = $_POST["name"]; $pw = hash("sha256", $_POST["password"]);
				$escaped_name = mysql_real_escape_string($name, $db_connection);

				if ($mode == "register" && isset($_POST["mail"]))
				{
					$should_redirect = false; # redirect after activation!
					$page_title = "Registration failed"; # assume failure, reset on success

					$mail = $_POST["mail"];
					$escaped_mail = mysql_real_escape_string($mail, $db_connection);

					$token = mt_rand();
					$joined = date("Y-m-d");

					# check if already registered
					$db_query = "SELECT name FROM $db_table_users WHERE mail = '$escaped_mail' OR name = '$escaped_name'";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("Failed to query for existing user.");

					if (mysql_num_rows($db_result) > 0)
					{
						$message = "User with this nickname or email is already registered.";
						break;
						#die ("User with this nickname or email is already registered."); # TODO: output in body
					}

					# register
					$db_query = "INSERT INTO $db_table_users (id, name, mail, pw, activationToken, joined) VALUES (UNHEX(REPLACE(UUID(), '-', '')), '$escaped_name', '$escaped_mail', '$pw', '$token', '$joined')";
					if (!mysql_query($db_query, $db_connection))
					{
						$message = "Failed to save new user: " . mysql_error(); # TODO: stop here
						break;
						#die ("Failed to save new user: " . mysql_error()); # TODO: output in body
					}

					$url = "http://" . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'] . "?name=$name&mode=activate&token=$token&redirect=" . urlencode($redirect);
					if (!mail($mail,
						"Confirm your registration to ALD",
						"To activate your account, go to <a href='$url'>$url</a>.",
						"FROM: noreply@{$_SERVER['HTTP_HOST']}\r\nContent-type: text/html; charset=iso-8859-1"))
					{
						# TODO: remove db entry
						$message = "Failed to send account activation mail to '$mail'!";
						break;
						#die("Failed to send mail to '$mail'!"); # TODO: output in body
					}

					$error = false;
					$message = "An activation mail was sent to the supplied mail address. Open the included link to activate your account.";
					$page_title = "Successfully registered!";
					#echo "An activation mail was sent to the supplied mail address. Open the included link to activate your account."; # TODO: output in body
				}
				else if ($mode == "activate")
				{
					if (isset($_GET["token"]))
					{
						$page_title = "Activation failed"; # assume failure, reset on success

						$token = mysql_real_escape_string($_GET["token"]);

						$db_query = "SELECT activationToken FROM $db_table_users WHERE name = '$escaped_name' AND activationToken = '$token' AND pw = '$pw'";
						if (!$db_result = mysql_query($db_query, $db_connection))
						{
							$message = "Failed to query user database: " . mysql_error();
							break;
							#die("Failed to query user database: " . mysql_error()); # TODO: output in body
						}

						if (mysql_num_rows($db_result) != 1)
						{
							$message = "User account with that name, password and token could not be found.";
							break;
							#die("User account with that name, password and token could not be found."); # TODO: output in body
						}

						$db_query = "UPDATE $db_table_users Set activationToken = '' WHERE name = '$escaped_name' AND activationToken = '$token' AND pw = '$pw'";
						if (!mysql_query($db_query, $db_connection))
						{
							$message = "Failed to reset activation token.";
							break;
							#die("Failed to reset activation token."); # TODO: output in body
						}

						$message = "Your account was successfully activated.";
						$page_title = "Account activated!";
						$error = false;
						#echo "Your account was successfully activated."; # TODO: output in body
					}
				}
				else if ($mode == "login")
				{
					$page_title = "Login failed"; # assume failure, reset on success
					$should_redirect = false;
					# ...
					#echo "NOT YET IMPLEMENTED / NOT REQUIRED!";
					require_once("api/User.php");
					if (User::validateLogin($_POST["name"], $_POST["password"], false))
					{
						try
						{
							$_SESSION["user"] = $name;
							$_SESSION["password"] = $_POST["password"];
							$_SESSION["privileges"] = User::getPrivileges(User::getID($name));
						}
						catch (HttpException $e)
						{
							session_destroy();
							$message = "Failed retrieving user data! ({$e->getMessage()})";
							break;
						}

						$page_title = "Successfully logged in!";
						$error = false;
						$should_redirect = true;
					}
					else
						$message = "Could not login";
				}
			}

			if ($error)
			{
				$should_redirect = false;
			}
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
		<?php
			if ($should_redirect)
			{
				echo "<meta http-equiv=\"REFRESH\" content=\"10;url=$redirect\">";
		?>
				<script type="text/javascript">
					$seconds = 9;
					function updateTime()
					{
						document.getElementById("sec").innerHTML = $seconds--;
					}
				</script>
		<?php
			}
		?>
	</head>
	<body <?php echo $should_redirect ? "onload=\"setInterval(updateTime, 999)\"" : ""; ?>>

		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>

		<div id="page-content">
			<?php
				if (empty($_POST) && $mode != "logout")
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
					if ($error)
					{
						echo "An error occured" . (!empty($message) ? ": $message" : "!");
					}
					else if (!empty($message))
					{
						echo $message;
					}
					if ($should_redirect)
					{
						echo "Redirecting to <a href=\"$redirect\">$redirect</a> in <span id=\"sec\">10</span> seconds...";
					}
				}
			?>
		</div>

		<?php require("footer.php"); ?>
	</body>
</html>