<?php
	session_start();

	require_once("config/constants.php");
	require_once("util/ALD.php");
	require_once("util/secure_redirect.php");
	require_once('partials/Notice.php');

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

			$api = new ALD(API_URL);
			$user_list = $api->getUserList();
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
				if ($mode == "login")
				{
					if (isset($_POST["keepLoggedIn"]) && $_POST["keepLoggedIn"])
					{
						session_set_cookie_params(8640000); # 100 Tage
					}

					$page_title = "Login failed"; # assume failure, reset on success
					$should_redirect = false;

					$api = new ALD( API_URL );

					try {
						$user = $api->getUser($_POST['name'], $_POST['name'], $_POST['password']); # provide credentials only for purpose of indirect validation
					} catch (HttpException $e) {
						if ($e->getCode() == 403) {
							$error_description = 'The given credentials were not valid.';
						} else {
							$error_description = 'The API error was: "' . $e->getMessage() . '"';
						}

						$message = 'Could not login';
						break;
					}

					$_SESSION["user"] = $_POST['name'];
					$_SESSION["userID"] = $user["id"];
					$_SESSION["password"] = $_POST["password"];
					$_SESSION["privileges"] = $user["privileges"];

					$page_title = "Successfully logged in!";
					$error = false;
					$should_redirect = true;
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
			<link rel="stylesheet" type="text/css" href="style/login.css"/>
		<?php
			require("partials/html.head.php");

			if ($should_redirect)
			{
				echo "<meta http-equiv=\"REFRESH\" content=\"10;url=$redirect\">";
		?>
				<script type="text/javascript" src="javascript/update_redirect_time.js"></script>
		<?php
			} else {
		?>
			<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
			<script type="text/javascript" src="javascript/jquery-ui.js"></script>
			<script type="text/javascript" src="javascript/modernizr.js"></script>
			<script type="text/javascript" src="javascript/polyfills/loadFormPolyfills.js"></script>
		<?php } ?>
	</head>
	<body <?php echo $should_redirect ? "onload=\"setInterval(update_redirect_time, 999)\"" : ""; ?>>
		<h1 id="page-title"><?php echo $page_title; ?></h1>

		<div id="page-content">
			<?php
				if (empty($_POST) && $mode != "logout")
				{
			?>
					<form action="<?php echo htmlentities($_SERVER["REQUEST_URI"]); ?>" method="post">
						<span class="advice">Enter your personal information below:</span>
						<label for="user-name">Nickname:</label>
						<input id="user-name" type="text" name="name" <?php echo isset($user_list) ? 'list="registered-users"' : ''; ?> required="required"/>
					<?php if (isset($user_list)) {
							echo '<datalist id="registered-users">';
							foreach ($user_list AS $user) {
								echo "<option value='$user[name]'></option>";
							}
							echo '</datalist>';
						}
					?>
						<label for="input_user_pw">Password:</label>
						<input id="input_user_pw" type="password" name="password" required="required"/>
						<label for="input_login_permanent">login permanently</label>
						<input type="checkbox" name="keepLoggedIn" id="input_login_permanent"/>
						<input type="submit" value="Login"/>
						<input type="reset" value="Reset"/>
					</form>
			<?php
				}
				else
				{
					if ($error)
					{
						error($message, $error_description, true);
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

		<?php require("partials/footer.php"); require("partials/header.php"); ?>
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