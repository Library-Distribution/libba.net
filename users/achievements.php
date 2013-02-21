<?php
	ob_start();
	session_start();

	if (!isset($_GET['user']))
	{
		header('Location: .');
	}

	require_once('../util/sortArray.php');
	require_once('../util/ALD.php');
	require_once('../config/constants.php');
	require_once('../partials/Notice.php');

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
			$error_message = 'Failed to retrieve user: API error';
			$error_description = 'User data could not be retrieved. API error was: "' . $e->getMessage() . '" (code: ' . $e->getCode() . ')';
			break;
		}
		$page_title = $user;

		$achievements = array();

		try
		{
			$libs = $api->getItemList(0, 'all', 'lib', $user, NULL, NULL, NULL, 'yes');
		}
		catch (HttpException $e)
		{
			$error_message = 'Failed to retrieve achievements: API error';
			$error_description = 'Libraries in stdlib could not be retrieved. API error was: "' . $e->getMessage() . '" (code: ' . $e->getCode() . ')';
			break;
		}
		$libs = sortArray($libs, array('name' => false, 'version' => true));

		foreach ($libs as $lib)
		{
			$achievements[] = array('text' => $user . '\'s library ' . $lib['name'] . ' v' . $lib['version'] . ' is part of the standard lib for AutoHotkey',
									'image' => 'images/achievements/stdlib.png',
									'link' => 'items/' . $lib['id']);
		}
		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require('../partials/html.head.php'); ?>
		<link rel="stylesheet" type="text/css" href="style/users/general.css"/>
		<link rel="stylesheet" type="text/css" href="style/users/achievements.css"/>
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
				else
				{
					echo '<ul>';
					foreach ($achievements AS $a)
					{
						echo '<li><a href="' . $a['link'] . '"><img class="achievement-icon" src="' . $a['image'] . '"/> ' . $a['text'] . '</a></li>';
					}
					echo '</ul>';
				}
			?>
		</div>
		<?php
			$current_mode = 'achievements';
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