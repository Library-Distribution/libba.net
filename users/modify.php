<?php
	ob_start();
	session_start();

	if (!isset($_GET['user']))
	{
		header('Location: .');
	}

	#require_once('../util/sortArray.php');
	require_once('../util/ALD.php');
	require_once('../config/constants.php');
	#require_once('util/user_input.php');
	require_once('../util/privilege.php');
	require_once('../util/db.php');
	require_once('../partials/Notice.php');

	require_once('../util/secure_redirect.php');
	secure_redirect();

	$api = new ALD( SECURE_API_URL );
	$logged_in = isset($_SESSION['user']);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		$user = $_GET['user'];
		try
		{
			$user_data = $api->getUser($user);
		}
		catch (HttpException $e)
		{
			$error_message = 'Failed to retrieve user: API error';
			$error_description = 'User data could not be retrieved. API error was: "' . $e->getMessage() . '" (code: ' . $e->getCode() . ')';
			break;
		}
		$page_title = $user;

		if (!$logged_in || $_SESSION['user'] != $user)
		{
			header('Location: .');
			exit;
		}

		$db_connection = db_ensure_connection();

		$db_query = 'SELECT * FROM ' . DB_TABLE_USER_PROFILE . ' WHERE id = UNHEX("' . $user_data['id'] . '")';
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = 'Failed to retrieve profile: MySQL error';
			$error_description = 'Could not read profile settings. MySQL error was: "' . mysql_error() . '"';
			break;
		}
		$user_profile = mysql_fetch_assoc($db_result);

		if (!db_get_enum_column_values(DB_TABLE_USER_PROFILE, 'allow_mails', $contact_options))
		{
			$error_message = 'Cannot modify profile: MySQL error';
			$error_description = 'The possible options for "allow_mails" could not be retrieved. MySQL error was: "' . mysql_error() . '"';
			break;
		}
		if (!db_get_enum_column_values(DB_TABLE_USER_PROFILE, 'show_mail', $mail_options))
		{
			$error_message = 'Cannot modify profile: MySQL error';
			$error_description = 'The possible options for "show_mail" could not be retrieved. MySQL error was: "' . mysql_error() . '"';
			break;
		}
		if (!db_get_enum_column_values(DB_TABLE_USER_PROFILE, 'site_theme', $theme_options))
		{
			$error_message = 'Cannot modify profile: MySQL error';
			$error_description = 'The possible options for "site_theme" could not be retrieved. MySQL error was: "' . mysql_error() . '"';
			break;
		}

		if (!empty($_POST))
		{
			# todo: verify password
			# require user to enter his password once again

			if (!empty($_POST['username']) && $_POST['username'] != $user)
			{
				try {
					$api->modifyUser($user, $_SESSION['password'], $_POST['username']);
				} catch (HttpException $e) {
					$error_message = 'Failed to update user profile: API error';
					$error_description = 'New user name could not be saved. API error was: "' . $e->getMessage() . '"';
					break;
				}
				$redirect_user = $_POST['username'];
				$_SESSION['user'] = $_POST['username'];
			}
			if (!empty($_POST['mail']) && $_POST['mail'] != $user_profile['mail'])
			{
				# todo: deactivate account, send activation mail

				$mail = mysql_real_escape_string($_POST['mail']);
				$db_query = 'UPDATE ' . DB_TABLE_USER_PROFILE . ' Set mail = "' . $mail . '" WHERE id = UNHEX("' . $_SESSION['userID'] . '")';

				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result || mysql_affected_rows() != 1)
				{
					$error_message = 'Failed to update user profile: MySQL error';
					$error_description = 'New email could not be saved. MySQL error was: "' . mysql_error() . '"';
					break;
				}
			}
			if (!empty($_POST['site_theme']) && $_POST['site_theme'] != $user_profile['site_theme'] && in_array($_POST['site_theme'], $theme_options))
			{
				$theme = mysql_real_escape_string($_POST['site_theme']);
				$db_query = 'UPDATE ' . DB_TABLE_USER_PROFILE . ' Set site_theme = "' . $theme . '" WHERE id = UNHEX("' . $_SESSION['userID'] . '")';

				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result || mysql_affected_rows() != 1)
				{
					$error_message = 'Failed to update user profile: MySQL error';
					$error_description = 'New website theme could not be saved. MySQL error was: "' . mysql_error() . '"';
					break;
				}
			}
			if (!empty($_POST['show_mail']) && $_POST['show_mail'] != $user_profile['show_mail'] && in_array($_POST['show_mail'], $mail_options))
			{	
				$show_mail = mysql_real_escape_string($_POST['show_mail']);
				$db_query = 'UPDATE ' . DB_TABLE_USER_PROFILE . ' Set show_mail = "' .$show_mail . '" WHERE id = UNHEX("' . $_SESSION['userID'] . '")';

				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result || mysql_affected_rows() != 1)
				{
					$error_message = 'Failed to update user profile: MySQL error';
					$error_description = 'New setting for email visibility could not be saved. MySQL error was: "' . mysql_error() . '"';
					break;
				}
			}
			if (!empty($_POST['allow_mails']) && $_POST['allow_mails'] != $user_profile['allow_mails'] && in_array($contact_options, $_POST['allow_mails']))
			{
				$allow_mails = mysql_real_escape_string($_POST['allow_mails']);
				$db_query = 'UPDATE ' . DB_TABLE_USER_PROFILE . ' Set allow_mails = "' . $allow_mails . '" WHERE id = UNHEX("' . $_SESSION['userID'] . '")';

				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result || mysql_affected_rows() != 1)
				{
					$error_message = 'Failed to update user profile: MySQL error';
					$error_description = 'New setting for allowing contacting could not be saved. MySQL error was: "' . mysql_error() . '"';
					break;
				}
			}
			# todo: support changing password
			if (isset($redirect_user))
			{
				header('Location: ?user=' . $redirect_user);
			}
		}

		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require('../partials/html.head.php'); ?>
		<link rel="stylesheet" type="text/css" href="style/users/general.css"/>
		<link rel="stylesheet" type="text/css" href="style/users/modify.css"/>
	</head>
	<body>
		<h1 id="page-title">
			<?php
				echo '<img alt="' . $user . '\'s avatar" id="user-gravatar" src="http://gravatar.com/avatar/' . $user_data['mail-md5'] . '?s=50&amp;d=mm"/>';
				echo $page_title;
			?>
		</h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					error($error_message, $error_description, true);
				}
				else if (empty($_POST))
				{
			?>
					<form action="#" method="post">
						<fieldset>
							<legend>user</legend>

							<label for="username">user name:</label>
							<input type="text" name="username" value="<?php echo $user; ?>"/>
							<div class="help" lang="en" data-help-advice="The nickname you use for logging in and that is displayed to other users"></div>

							<label for="mail">email:</label>
							<input type="text" name="mail" value="<?php echo $user_profile['mail']; ?>"/>
							<div class="help" lang="en" data-help-advice="The email address associated with your profile. You can control its visibility below."></div>
						</fieldset>

						<fieldset>
							<legend>display</legend>

							<label for="site_theme">website theme:</label>
							<select name="site_theme">
								<?php
								foreach ($theme_options AS $theme)
								{
									echo '<option value="' . $theme . '" '
											. ($user_profile['site_theme'] == $theme ? 'selected="selected"' : '')
											. '>' . $theme . '</option>';
								}
								?>
							</select>
							<div class="help" lang="en" data-help-advice="This changes how the website is presented to you when logged in."></div>
						</fieldset>

						<fieldset>
							<legend>privacy</legend>

							<label for="show_mail">email visibility:</label>
							<select name="show_mail">
								<?php
								foreach ($mail_options AS $option)
								{
									echo '<option value="' . $option . '" '
											. ($user_profile['show_mail'] == $option ? 'selected="selected"' : '')
											. '>' . $option . '</option>';
								}
								?>
							</select>
							<div class="help" lang="en" data-help-advice="To protect your mail address from spambots, it is embedded as image (if at all)"></div>

							<label for="allow_mails">allow contact by:</label>
							<select name="allow_mails">
								<?php
									foreach ($contact_options AS $value)
									{
										echo '<option value="' . $value . '" '
												. ($user_profile['allow_mails'] == $value ? 'selected="selected"' : '')
												. '>' . $value . '</option>';
									}
								?>
							</select>
							<div class="help" lang="en" data-help-advice="Contacting works without the sender seeing your mail address. Moderators can always contact you."></div>
						</fieldset>
						<!-- TODO: support password change (enter twice) -->

						<input type="submit"/>
						<input type="reset"/>
					</form>
			<?php
				}
				else
				{
					echo 'Your profile has been updated.';
				}
			?>
		</div>
		<?php
			$current_mode = 'modify';
			require_once('user_navigation.php');

			require('../partials/footer.php');
			require('../partials/header.php');
		?>
	</body>
</html>
<?php
	require_once('../util/rewriter.php');
	echo rewrite();
	ob_end_flush();
?>