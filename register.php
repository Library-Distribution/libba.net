<?php
	session_start();
	ob_start();

	require_once("config/constants.php");
	require_once("ALD.php");
	require_once("secure_redirect.php");
	secure_redirect();

	$page_title = "Registration failed";
	$mode = isset($_GET["mode"]) ? strtolower($_GET["mode"]) : "init";
	$error = true;
	$api = new ALD( API_URL );

	if (empty($_POST))
	{
		if ($mode == "init")
		{
			$page_title = "Register";
		}
		else if ($mode == "verify")
		{
			$page_title = "Complete your registration";
		}
		$error = false;
	}
	else
	{
		for ($i = 0; $i < 1; $i++)
		{
			if ($mode == "init")
			{
				if (empty($_POST["name"]) || empty($_POST["mail"]) || empty($_POST["password"]) || empty($_POST["password_alt"]))
				{
					$message = "Data missing";
					$error_description = "Not all data required for a registration is present.";
					break;
				}

				require("templates/registration.php");
				try
				{
					$api->initRegistration( $_POST["name"], $_POST["mail"], $_POST["password"], $_POST["password_alt"], $template );
				}
				catch (HttpException $e)
				{
					$message = "Failed to initiate registration";
					$error_description = "The attempt to initiate a registration failed. Error code was: '{$e->getCode()}'. Error message was: '{$e->getMessage()}'.";
					break;
				}
				$message = "Registration has been initiated successfully. Check your email account for further details.";
				$page_title = "Registration initiated";
			}
			else if ($mode == "verify" && isset($_GET["id"]))
			{
				if (empty($_POST["token"]))
				{
					$message = "Data missing";
					$error_description = "Not all data required for completing your rgistration is present.";
					break;
				}

				try
				{
					$api->completeRegistration( $_GET["id"], $_POST["token"] );
				}
				catch (HttpException $e)
				{
					$message = "Failed to complete registration";
					$error_description = "Completing the registration with the given token failed. Error message was: '{$e->getMessage()}'.";
					break;
				}
				$message = "Your registration has successfully been completed.";
				$page_title = "Registration successful";
			}

			$error = false;
		}
	}
?>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (empty($_POST))
				{
					if ($mode == "verify")
					{
				?>
						<div>
							Look at the image below and type the displayed letters in the box.
							<br/><img alt="Token" src="<?php echo API_URL; ?>/users/register/token/<?php echo $_GET["id"]; ?>"/>
							<br/>Image available at: <a href="<?php echo echo API_URL; ?>/users/register/token/<?php echo $_GET["id"]; ?>"><?php echo echo API_URL; ?>/users/register/token/<?php echo $_GET["id"]; ?></a>
						</div>
				<?php } ?>
					<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
						<table>
							<?php if ($mode == "verify") { ?>
								<tr>
									<td><label for="token">Token:</label></td>
									<td><input name="token" type="text"/></td>
								</tr>
							<?php } else if ($mode == "init") { ?>
								<tr>
									<td><label for="name">user name:</label></td>
									<td><input name="name" type="text"></input></td>
								</tr>
								<tr>
									<td><label for="mail">email:</label></td>
									<td><input name="mail" type="text"></input></td>
								</tr>
								<tr>
									<td><label for="password">password:</label></td>
									<td><input name="password" type="password"></input></td>
								</tr>
								<tr>
									<td><label for="password_alt">repeat password:</label></td>
									<td><input name="password_alt" type="password"></input></td>
								</tr>
							<?php } ?>
								<tr>
									<td><input type="submit" value="submit"/></td>
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
				}
			?>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>
<?php
	require_once("rewriter.php");
	echo rewrite();
	ob_end_flush();
?>