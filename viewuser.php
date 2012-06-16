<?php
	# This function was taken from http://www.the-art-of-web.com/php/sortarray/ (2012/06/06) and has been created by Thomas Heuer (Germany). Thanks a lot for this!
	#It has been modified by me (maul.esel) to use objects instead of arrays and to allow both ascending and descending sorting.
	function sortArray($data, $field)
	{
		if (!is_array($field))
		{
			$field = array($field => false);
		}
		usort($data, function($a, $b) use($field)
		{
			$retval = 0;
			foreach($field as $fieldname => $direction)
			{
				if($retval == 0)
				{
					if (!$direction)
						$retval = strnatcasecmp($a->$fieldname, $b->$fieldname);
					else
						$retval = strnatcasecmp($b->$fieldname, $a->$fieldname);
				}
			}
			return $retval;
		});
		return $data;
	}

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
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
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
								echo "<li><a href='?user=$user->name'>$user->name</a></li>";
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

					echo "<div id=\"user-gravatar\"><img width=\"200\" height=\"200\" src=\"http://gravatar.com/avatar/{$user_data->mail}?s=200&d=mm\"/></div>";
					echo "Joined: <span class='joined-date'>$user_data->joined</span>";

					$items = $api->getItemList(0, "all", "lib", $user);
					$items = sortArray($items, array("name" => false, "version" => true));

					if ($item_count = count($items))
					{
						echo "<h2>Libraries uploaded ($item_count) :</h2><ul>";
						$uploaded = array();
						foreach ($items AS $item)
						{
							if (!in_array($item->name, $uploaded))
							{
								$uploaded[] = $item->name;
								echo "<li><a href='viewitem?id=$item->id'>$item->name (v$item->version)</a></li>";
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
							if (!in_array($item->name, $uploaded))
							{
								$uploaded[] = $item->name;
								echo "<li><a href='viewitem?id=$item->id'>$item->name (v$item->version)</a></li>";
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
