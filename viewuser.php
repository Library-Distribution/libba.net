<?php
	session_start();

	require_once("sortArray.php");
	require_once("ALD.php");

	$api = new ALD(!empty($_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");
	$logged_in = isset($_SESSION["user"]);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		if (isset($_GET["user"]))
		{
			$user = $_GET["user"];
			$page_title = "User: $user";
			$user_data = $api->getUser($user);

			$libs = $api->getItemList(0, "all", "lib", $user, NULL, NULL, "latest");
			$libs = sortArray($libs, array("name" => false, "version" => true));

			$apps = $api->getItemList(0, "all", "app", $user, NULL, NULL, "latest");
			$apps = sortArray($apps, array("name" => false, "version" => true));
		}
		else
		{
			$page_title = "View users";

			$page_index = !empty($_GET["page"]) ? (int)$_GET["page"] : 0;
			$page_itemcount = !empty($_GET["items"]) ? (int)$_GET["items"] : 15;
			$start_index = $page_index * $page_itemcount;

			try
			{
				$users = $api->getUserList($start_index, $page_itemcount + 1);
			}
			catch (HttpException $e)
			{
				$error_message = "Failed to get user list: API error";
				$error_description = "The list of users could not be retrieved. API error was: '{$e->getMessage()}'";
				break;
			}
			$users = sortArray($users, "name");
		}
		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					require("error.php");
				}
				else if (!isset($user)) # output a list of users
				{
					echo "<ul>";
					$i = 0;
					foreach ($users AS $user)
					{
						$i++;
						if ($i > $page_itemcount)
						{
							break;
						}
						echo "<li><a href='?user={$user['name']}'>{$user['name']}</a></li>";
					}
					echo "</ul>";

					if (count($users) == 0)
					{
						echo "No users found";
					}

					if ($page_index > 0)
					{
						echo "<a class='next-previous' id='prev' href='?items=$page_itemcount&amp;page=".($page_index - 1)."'>Previous page</a>";
					}

					# check if there are more users
					if (count($users) > $page_itemcount)
					{
						echo "<a class='next-previous' id='next' href='?items=$page_itemcount&amp;page=".($page_index + 1)."'>Next page</a>";
					}
				}
				else # output a user profile
				{
					if ($logged_in)
					{
						require_once("privilege.php");
						if (hasPrivilege($_SESSION["privileges"], PRIVILEGE_USER_MANAGE))
						{
							$redirect_url = urlencode($_SERVER["REQUEST_URI"]);
							echo "<div class=\"menu\">User management<ul class=\"admin-menu\">";
							if ($user_data["enabled"])
							{
								echo "<a href='moderator-action.php?user={$user_data['name']}&action=suspend&value=1&return_error=true&redirect=$redirect_url'><li><span style=\"font-weight: bold; color: red\">Suspend</span> user</li></a>";
							}
							else
							{
								echo "<a href='moderator-action.php?user={$user_data['name']}&action=suspend&value=-1&return_error=true&redirect=$redirect_url'><li><span style=\"font-weight: bold; color: green\">Unsuspend</span> user</li></a>";
							}
							echo "</ul></div>";
						}
					}

					echo "<div id=\"user-gravatar\"><img width=\"200\" height=\"200\" src=\"http://gravatar.com/avatar/{$user_data['mail']}?s=200&d=mm\"/></div>";

					if ($item_count = count($libs))
					{
						echo "<h2>Libraries uploaded ($item_count) :</h2><ul>";
						foreach ($libs AS $lib)
						{
							echo "<li><a href='viewitem?id={$lib['id']}'>{$lib['name']} (v{$lib['version']})</a></li>";
						}
						echo "</ul>";
					}

					if ($item_count = count($apps))
					{
						echo "<h2>Applications uploaded ($item_count) :</h2><ul>";
						foreach ($apps AS $app)
						{
							echo "<li><a href='viewitem?id={$app['id']}'>{$app['name']} (v{$app['version']})</a></li>";
						}
						echo "</ul>";
					}
				}
			?>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>
