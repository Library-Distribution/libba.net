<?php
	session_start();
	ob_start();

	require_once('config/constants.php');
	require_once('util/ALD.php');
	require_once('util/secure_redirect.php');
	require_once('partials/Notice.php');

	secure_redirect();

	$page_title = 'Registration failed';
	$mode = isset($_GET['mode']) ? strtolower($_GET['mode']) : 'init';
	$error = true;
	$api = new ALD( API_URL );

	if (empty($_POST))
	{
		if ($mode == 'init')
		{
			$page_title = 'Register';
		}
		else if ($mode == 'verify')
		{
			$page_title = 'Complete your registration';
		}
		$error = false;
	}
	else
	{
		for ($i = 0; $i < 1; $i++)
		{
			if ($mode == 'init')
			{
				if (empty($_POST['name']) || empty($_POST['mail']) || empty($_POST['password']) || empty($_POST['password_alt']))
				{
					$message = 'Data missing';
					$error_description = 'Not all data required for a registration is present.';
					break;
				}
				try
				{
					$api->initRegistration( $_POST['name'], $_POST['mail'], $_POST['password'], $_POST['password_alt'] );
				}
				catch (HttpException $e)
				{
					$message = 'Failed to initiate registration';
					$error_description = 'The attempt to initiate a registration failed. Error code was: "' . $e->getCode() . '". Error message was: "' . $e->getMessage() . '".';
					break;
				}
				$message = 'Registration has been initiated successfully. Check your email account for further details.';
				$page_title = 'Registration initiated';
			}
			else if ($mode == 'verify' && isset($_GET['id']))
			{
				if (empty($_POST['token']))
				{
					$message = 'Data missing';
					$error_description = 'Not all data required for completing your rgistration is present.';
					break;
				}

				try
				{
					$api->completeRegistration( $_GET['id'], $_POST['token'] );
				}
				catch (HttpException $e)
				{
					$message = 'Failed to complete registration';
					$error_description = 'Completing the registration with the given token failed. Error message was: "' . $e->getMessage() . '"';
					break;
				}
				$message = 'Your registration has successfully been completed.';
				$page_title = 'Registration successful';
			}

			$error = false;
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require('partials/html.head.php'); ?>
		<link rel="stylesheet" type="text/css" href="style/register.css"/>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (empty($_POST))
				{
					if ($mode == 'verify')
					{
				?>
						<div>
							Look at the image below and type the displayed letters in the box.
							<br/><img alt="Token" src="<?php echo API_URL; ?>/users/registration/token/<?php echo $_GET['id']; ?>"/>
							<br/>Image available at: <a href="<?php echo API_URL; ?>/users/registration/token/<?php echo $_GET['id']; ?>"><?php echo API_URL; ?>/users/register/token/<?php echo $_GET['id']; ?></a>
						</div>
				<?php } ?>
					<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
					<?php if ($mode == 'verify') { ?>
						<label for="token">Token:</label>
						<input name="token" type="text"/>

					<?php } else if ($mode == 'init') { ?>
						<span class="advice">Enter the data for the account you want to create:</span>

						<label for="name">user name:</label>
						<input name="name" type="text" placeholder="Enter your future user name..."></input>

						<label for="mail">email:</label>
						<input name="mail" type="text" placeholder="Enter your email address for this account..."></input>

						<label for="password">password:</label>
						<input name="password" type="password" placeholder="The password for your account..."></input>

						<label for="password_alt">repeat password:</label>
						<input name="password_alt" type="password" placeholder="Just to be sure, type the password again..."></input>

					<?php } ?>
						<input type="submit"/>
						<input type="reset"/>
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
				}
			?>
		</div>
		<?php require('partials/footer.php'); require('partials/header.php'); ?>
	</body>
</html>
<?php
	require_once('util/rewriter.php');
	echo rewrite();
	ob_end_flush();
?>