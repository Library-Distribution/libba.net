<?php
	ob_start();
	session_start();

	if (!isset($_GET['user']))
	{
		header('Location: .'); # redirect to user list
	}

	require_once('../sortArray.php');
	require_once('../ALD.php');
	require_once('../config/constants.php');
	require_once('../api/db.php');
	require_once('../db2.php');

	$api = new ALD( API_URL );
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
			$error_message = "Failed to retrieve user '$user': API error";
			$error_description = "User data could not be retrieved. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
			break;
		}

		$page_title = $user;
		$db_connection = db_ensure_connection();

		$db_query = "SELECT * FROM $db_table_user_profile WHERE id = UNHEX('{$user_data['id']}')";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = 'Failed to retrieve profile: MySQL error';
			$error_description = 'Could not read profile settings. MySQL error was: "' . mysql_error() . '"';
			break;
		}
		$user_profile = mysql_fetch_assoc($db_result);

		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php
			require('../templates/html.head.php');
		?>
		<link rel="stylesheet" type="text/css" href="style/users/general.css"/>
		<link rel="stylesheet" type="text/css" href="style/users/profile.css"/>
	</head>
	<body>
		<h1 id="page-title">
			<?php
				echo "<img alt='$user's avatar' id='user-gravatar' src='http://gravatar.com/avatar/{$user_data['mail']}?s=50&amp;d=mm'/>";
				echo $page_title;
			?>
		</h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					require('../error.php');
				}
				else # output a user profile
				{
				?>
					<table>
							<tr>
								<td>email:</td>
								<td>
				<?php
					if ($user_profile['show_mail'] == 'public' || ($user_profile['show_mail'] == 'members' && $logged_in))
					{
						echo "<img id='user-mail' alt='$user's mail address' src='mailimage.php?user={$user_data['id']}'/>";
					}
					if ($user_profile['allow_mails'])
					{
						echo "<a href='#'>Contact $user</a>";
					}
				?>
								</td>
							</tr>
							<tr>
								<td>member since:</td>
								<td><?php echo $user_data['joined']; ?></td>
							</tr>
							<tr>
								<td>user ID:</td>
								<td><?php echo $user_data['id']; ?></td>
							</tr>
						</table>
				<?php
				}
			?>
		</div>
		<?php
			$current_mode = 'profile';
			require_once('user_navigation.php');

			require('../footer.php');
			require('../header.php');
		?>
	</body>
</html>
<?php
	require_once('../rewriter.php');
	echo rewrite();
	ob_end_flush();
?>