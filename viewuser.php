<?php
	session_start();
	require_once("sortArray.php");

	$logged_in = isset($_SESSION["user"]);
	$page_title = "View users";

	if (isset($_GET["user"]))
	{
		$user = $_GET["user"];
		$page_title = "User: $user";
	}
	else
	{
		$page_index = 0;
		if (isset($_GET["page"]))
		{
			$page_index = $_GET["page"];
		}

		$page_itemcount = 15;
		if (isset($_GET["items"]))
		{
			$page_itemcount = $_GET["items"];
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
	</head>
	<body>
		<?php require("header.php") ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				require_once("ALD.php");
				$api = new ALD(!empty($_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");

				if (!isset($user)) # output a list of users
				{
					$start_index = $page_index * $page_itemcount;
					try
					{
						$users = $api->getUserList($start_index, $page_itemcount + 1);
					}
					catch (HttpException $e)
					{
						echo $e->getMessage();
					}

					if (isset($users))
					{
						sortArray($users, "name");

						if (count($users) > 0)
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
						}
						else
						{
							echo "No more users found";
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
				}
				else # output a user profile
				{
					$user_data = $api->getUser($user);

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
					echo "Joined: <span class='joined-date'>{$user_data['joined']}</span>";

					$items = $api->getItemList(0, "all", "lib", $user);
					$items = sortArray($items, array("name" => false, "version" => true));

					if ($item_count = count($items))
					{
						echo "<h2>Libraries uploaded ($item_count) :</h2><ul>";
						$uploaded = array();
						foreach ($items AS $item)
						{
							if (!in_array($item['name'], $uploaded))
							{
								$uploaded[] = $item['name'];
								echo "<li><a href='viewitem?id={$item['id']}'>{$item['name']} (v{$item['version']})</a></li>";
							}
						}
						echo "</ul>";
					}

					$items = $api->getItemList(0, "all", "app", $user);
					$items = sortArray($items, array("name" => false, "version" => true));

					if ($item_count = count($items))
					{
						echo "<h2>Applications uploaded ($item_count) :</h2><ul>";
						$uploaded = array();
						foreach ($items AS $item)
						{
							if (!in_array($item['name'], $uploaded))
							{
								$uploaded[] = $item['name'];
								echo "<li><a href='viewitem?id={$item['id']}'>{$item['name']} (v{$item['version']})</a></li>";
							}
						}
						echo "</ul>";
					}
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>
