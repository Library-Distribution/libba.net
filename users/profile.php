<?php
	ob_start();
	session_start();

	if (!isset($_GET["user"]))
	{
		header("Location: ."); # redirect to user list
	}

	require_once("../util/sortArray.php");
	require_once("../util/ALD.php");
	require_once("../config/constants.php");
	require_once("../util/db.php");
	require_once('../util/privilege.php');
	require_once('../util/get_privilege_symbols.php');
	require_once('../partials/Notice.php');

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION["user"]);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		$user = $_GET["user"];
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

		$db_query = "SELECT * FROM $db_table_user_profile WHERE id = UNHEX('{$user_data["id"]}')";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = "Failed to retrieve profile: MySQL error";
			$error_description = "Could not read profile settings. MySQL error was: '" . mysql_error() . "'";
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
			require("../partials/html.head.php");
		?>
		<link rel="stylesheet" type="text/css" href="style/users/general.css"/>
		<link rel="stylesheet" type="text/css" href="style/users/profile.css"/>
	</head>
	<body>
		<h1 id="page-title">
			<?php
				echo "<img alt=\"$user's avatar\" id=\"user-gravatar\" src=\"http://gravatar.com/avatar/{$user_data['mail-md5']}?s=50&amp;d=mm\"/>";
				echo $page_title;
			?>
		</h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					error($error_message, $error_description, true);
				}
				else # output a user profile
				{
				?>
					<span class='label'>email:</span>
				<?php
					if ($user_profile["show_mail"] == "public" || ($user_profile["show_mail"] == "members" && $logged_in))
					{
						echo "<img class='info' id=\"user-mail\" alt=\"$user's mail address\" src=\"internal/mailimage.php?user={$user_data["id"]}\"/>";
					}
					if ($user_profile["allow_mails"])
					{
						echo "<a class='info' href=\"#\">Contact $user</a>";
					}
				?>
					<span class='label'>member since:</span>
					<span class='info'><?php echo $user_data["joined"]; ?></span>

					<span class='label'>user ID:</span>
					<span class='info'><?php echo $user_data["id"]; ?></span>

				<?php
					if (hasExtendedPrivileges($user_data['privileges'])) {
				?>
					<span class='label'>privileges:</span>
					<span class='info'><?php echo get_privilege_symbols($user_data['privileges']); ?></span>
				<?php
					}
				}
			?>
		</div>
		<?php
			$current_mode = "profile";
			require_once("user_navigation.php");

			require("../partials/footer.php");
			require("../partials/header.php");
		?>
	</body>
</html>
<?php
	require_once("../util/rewriter.php");
	echo rewrite();
	ob_end_flush();
?>