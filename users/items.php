<?php
	ob_start();
	session_start();

	if (!isset($_GET["user"]))
	{
		header("Location: .");
	}

	require_once("../sortArray.php");
	require_once("../ALD.php");
	require_once("../config/constants.php");

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
			$error_message = "Failed to retrieve user: API error";
			$error_description = "User data could not be retrieved. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
			break;
		}
		$page_title = $user;
		
		try
		{
			$libs = $api->getItemList(0, "all", "lib", $user, NULL, NULL, "latest");
			$apps = $api->getItemList(0, "all", "app", $user, NULL, NULL, "latest");
		}
		catch (HttpException $e)
		{
			$error_message = "Failed to retrieve uploaded items: API error";
			$error_description = "Uploaded items could not be retrieved. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
			break;
		}
		$libs = sortArray($libs, array("name" => false, "version" => true));
		$apps = sortArray($apps, array("name" => false, "version" => true));
		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("../templates/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/users/general.css"/>
		<link rel="stylesheet" type="text/css" href="style/users/items.css"/>

		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="javascript/jquery-ui.js"></script>
		<script type="text/javascript" src="javascript/users/preload.js"></script>
	</head>
	<body class="pretty-ui">
		<h1 id="page-title">
			<?php
				echo "<img alt=\"$user's avatar\" id=\"user-gravatar\" src=\"http://gravatar.com/avatar/{$user_data['mail-md5']}?s=50&amp;d=mm\"/>";
				echo $page_title;
			?>
		</h1>
		<div id="page-content">
			<div id="content-items">
			<?php
				if ($error)
				{
					require("../error.php");
				}
				else # output apps and lib uploaded
				{
					foreach (array("Libraries" => $libs, "Applications" => $apps) AS $set_name => $set)
					{
						if ($item_count = count($set))
						{
							echo "<h2>$set_name uploaded ($item_count)</h2>";
							foreach ($set AS $item)
							{
								echo "<a href='items/{$item['id']}' class=\"user-item\">{$item['name']} (v{$item['version']})</a>";
							}
						}
					}
				}
			?>
			</div>
		</div>
		<?php
			$current_mode = "items";
			require_once("user_navigation.php");

			require("../footer.php");
			require("../header.php");
		?>
	</body>
</html>
<?php
	require_once("../rewriter.php");
	echo rewrite();
	ob_end_flush();
?>