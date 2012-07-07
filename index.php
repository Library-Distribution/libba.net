<?php session_start(); ?>
<!DOCTYPE html>
<html>
	<?php
		# 'GET' parameters:
		# Name		possible values			Meaning
		###########################################################################################################
		# page		any integer >= 0		Show the page with specified index. Omit to show the main page.
		#									The parameters below only have effect when this is one is present.
		# mode		"apps", "libs"			Only show applications or libraries in the listing. Omit to show both.
		# items		any integer >= 1		Show the specified amount of items on a page. Omit to use 20.
		# user		any valid user name		Only show items by the specified user. Omit to show items by all users.
		# todo: time (newer than, older than)

		$page_title = "Browse ";
		$mode = "";
		if (isset($_GET["mode"]))
		{
			$mode = $_GET["mode"];
			if ($mode == "apps")
			{
				$page_title .= "applications";
			}
			else if ($mode == "libs")
			{
				$page_title .= "libraries";
			}
		}
		else
		{
			$page_title .= "libraries and applications";
		}

		if (isset($_GET["page"]))
		{
			$page_index = $_GET["page"];
		}
		if (isset($_GET["user"]))
		{
			$user = $_GET["user"];
			$page_title .= " by $user";
		}
		if (isset($_GET["tags"]))
		{
			$tags = $_GET["tags"];
		}

		$page_itemcount = (empty($_GET["items"])) ? 20 : $_GET["items"];
	?>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<link rel="stylesheet" href="index.css"/>
		<title><?php echo $page_title; ?></title>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (!isset($page_index) && !isset($_GET["items"]) && !isset($user) && !isset($tag))
				{
			?>
					<p>
						<span class="text-first-word">Welcome</span> to <b><abbr>ALD</abbr></b>, the <b>A</b>utoHotkey <b>L</b>ibrary <b>D</b>istribution system.
						This is a standardized system for distribution of code you have written in AutoHotkey.
						By uploading your code here, you can make it accessible for every AutoHotkey user.
						See the list below for apps and libraries already available,
						or check out the manual to see how you can upload your own software.
					</p>
			<?php
				}
				if (!isset($page_index))
				{
					$page_index = 0;
				}
				require_once("ALD.php");
				require_once("sortArray.php");

				$item_type = ($mode == "apps" ? "app" : ($mode == "libs" ? "lib" : ""));
				$start_index = $page_index * $page_itemcount;

				$api = new ALD((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");
				$items = $api->getItemList($start_index, $page_itemcount + 1, isset($item_type) ? $item_type : NULL, !empty($user) ? $user : NULL, NULL, isset($tags) ? explode("|", $tags) : NULL, "latest");
				# TODO: name not supported by this page

				$last_letter = "";
				$i = 0;
				$items = sortArray($items, "name");
				foreach ($items as $item)
				{
					$i++;
					if ($i > $page_itemcount)
					{
						break;
					}

					$current_letter = strtoupper(substr($item->name, 0, 1));
					if (!ctype_alpha($current_letter))
					{
						$current_letter = ".#?1";
					}
					if ($current_letter != $last_letter)
					{
						if ($last_letter != "")
						{
							echo "</ul></div>";
						}
						echo "<div class='letter-container' id='items$current_letter'><span class='letter-item'>$current_letter</span><ul>";
					}
					echo "<li><a class='item' name='item$item->id' href='viewitem?id=$item->id'>$item->name</a> (v$item->version) by <a class='userlink' href='viewuser?user=$item->user'>$item->user</a></li>";
					$last_letter = $current_letter;
				}
				if (count($items) > 0)
				{
					echo "</ul></div>";
				}
				else
				{
					echo "<b>No items found that match your query.</b>";
				}

				if ($page_index > 0)
				{
					echo "<a class='next-previous' id='prev' href='?items=$page_itemcount&amp;page=".($page_index - 1)."'>Previous page</a>";
				}

				if (count($items) > $page_itemcount)
				{
					echo "<a class='next-previous' id='next' href='?items=$page_itemcount&amp;page=".($page_index + 1)."'>Next page</a>";
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>
