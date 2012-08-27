<?php
	session_start();
	date_default_timezone_set("UTC");

	require_once("config/constants.php");
	require_once("ALD.php");
	require_once("secure_redirect.php");
	secure_redirect();

	$page_title = "";
	$mode = isset($_GET["mode"]) ? $_GET["mode"] : "login";
	$error = true;
	$redirect = (empty($_GET["redirect"])) ? "index" : urldecode($_GET["redirect"]);
	$should_redirect = true;

	if (empty($_POST))
	{
		$should_redirect = false;
		$error = false;

		if ($mode == "login")
		{
			$page_title = "Login";
		}
		else if ($mode == "activate")
		{
			$page_title = "Activate account";
		}
		else if ($mode == "logout")
		{
			$page_title = "Logged out";
			clearSession();
			$should_redirect = true;
		}
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

				if ($mode == "activate")
				{
					if (isset($_GET["token"]))
					{
						$page_title = "Activation failed"; # assume failure, reset on success
						$token = mysql_real_escape_string($_GET["token"]);

						$db_query = "SELECT activationToken FROM $db_table_users WHERE name = '$escaped_name' AND activationToken = '$token' AND pw = '$pw'";
						if (!$db_result = mysql_query($db_query, $db_connection))
						{
							$message = "Could not validate activation token";
							$error_description = "Failed to validate the activation token. The error message was: \"" . mysql_error . "\". Until the token is cleared, the account is still deactivated.";
							break;
						}

						if (mysql_num_rows($db_result) != 1)
						{
							$message = "Account not found";
							$error_description = "A user account with that user name ($name), password and token could not be found. Therefore it could not be activated.";
							break;
						}

						$db_query = "UPDATE $db_table_users Set activationToken = '' WHERE name = '$escaped_name' AND activationToken = '$token' AND pw = '$pw'";
						if (!mysql_query($db_query, $db_connection))
						{
							$message = "Could not reset activation token";
							$error_description = "Failed to empty the activation token. The error message was: \"" . mysql_error . "\". Until the token is cleared, the account is still deactivated.";
							break;
						}

						$message = "Your account was successfully activated.";
						$page_title = "Account activated!";
						$error = false;
					}
				}
				else if ($mode == "login")
				{
					$page_title = "Login failed"; # assume failure, reset on success
					$should_redirect = false;

					require_once("api/User.php");

					if (User::validateLogin($_POST["name"], $_POST["password"], false))
					{
						try
						{
							$api = new ALD( API_URL );
							$user = $api->getUser($name);

							$_SESSION["user"] = $name;
							$_SESSION["userID"] = $user["id"];
							$_SESSION["password"] = $_POST["password"];
							$_SESSION["privileges"] = $user["privileges"];
						}
						catch (HttpException $e)
						{
							clearSession();

							$message = "Could not login";
							$error_description = "Could not retrieve the required user data for a login. The exception message was: \"{$e->getMessage()}\".";
							break;
						}

						$page_title = "Successfully logged in!";
						$error = false;
						$should_redirect = true;
					}
					else
					{
						$message = "Could not login";
						$error_description = "The given credentials were not valid.";
						break;
					}
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
		<?php
			require("templates/html.head.php");

			if ($should_redirect)
			{
				echo "<meta http-equiv=\"REFRESH\" content=\"10;url=$redirect\">";
		?>
				<script type="text/javascript" src="javascript/update_redirect_time.js"></script>
		<?php
			}
		?>
	</head>
	<body <?php echo $should_redirect ? "onload=\"setInterval(update_redirect_time, 999)\"" : ""; ?>>
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
			<?php	} ?>
							<tr>
								<td>Password:</td>
								<td><input type="password" name="password"/></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" value="<?php echo ($mode == "login") ? "Login" : "Activate"; ?>"/></td>
							</tr>
						</table>
					</form>
			<?php
				}
				else
				{
					if ($error)
					{
						$error_message = $message;
						require("error.php");
					}
					else if (!empty($message))
					{
						echo $message;
					}
					if ($should_redirect)
					{
						echo "<p>Redirecting to <a href=\"$redirect\">$redirect</a> in <span id=\"sec\">10</span> seconds...</p>";
					}
				}
			?>
		</div>

		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>
<?php
	function clearSession()
	{
		foreach (array_keys($_SESSION) AS $key)
			unset($_SESSION[$key]); # unset here as session_destroy() has no effect on currently loaded page
		session_destroy();
	}
?>