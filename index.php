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
		if (isset($_GET["tag"]))
		{
			$tag = $_GET["tag"];
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
				require("db.php");
				require("users.php");

				# connect to database server
				$db_connection = db_ensureConnection();
				$page_itemcount = mysql_real_escape_string($page_itemcount, $db_connection);

				$db_query_cond = "";
				if ($mode == "apps")
				{
					$db_query_cond = "WHERE type = 'app'";
				}
				else if ($mode == "libs")
				{
					$db_query_cond = "WHERE type = 'lib'";
				}
				if (!empty($user))
				{
					$user = mysql_real_escape_string($user, $db_connection);
					if ($db_query_cond == "")
					{
						$db_query_cond = "WHERE user = '$user'";
					}
					else
					{
						$db_query_cond .= " AND user = '$user'";
					}
				}
				if (isset($tag))
				{
					$tag = mysql_real_escape_string($tag, $db_connection);
					if ($db_query_cond == "")
					{
						$db_query_cond = "WHERE tags REGEXP '(^|;)$tag($|;)'";
					}
					else
					{
						$db_query_cond .= " AND tags REGEXP '(^|;)$tag($|;)'";
					}
				}

				$start_index = ($page_index) * $page_itemcount;
				$db_query = "SELECT HEX(id), name, version, HEX(user) FROM $db_table_main $db_query_cond ORDER BY name LIMIT $start_index,$page_itemcount";
				$db_result = mysql_query($db_query, $db_connection)
				or die ("Could not retrieve list of apps and libraries.".mysql_error());

				$items = array();
				while ($item = mysql_fetch_assoc($db_result))
				{
					$items[$item['name']] = $item;
				}

				$last_letter = "";
				foreach ($items as $item_name => $item)
				{
					$user = user_get_nick($item['HEX(user)']);
					$current_letter = strtoupper(substr($item_name, 0, 1));
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
					echo "<li><a class='item' name='item{$item['HEX(id)']}' href='viewitem.php?id={$item['HEX(id)']}'>$item_name</a> (v{$item['version']}) by <a class='userlink' href='viewuser.php?user=$user'>$user</a></li>";
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

				if (count($items) > 0)
				{
					# check if there are more items
					$db_query = "SELECT id FROM $db_table_main $db_query_cond ORDER BY name LIMIT ".($start_index + $page_itemcount).",1";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("ERROR: Could not query for more items\n".mysql_error());
					if (mysql_num_rows($db_result) > 0) # if so, show the "next" link
					{
						echo "<a class='next-previous' id='next' href='?items=$page_itemcount&amp;page=".($page_index + 1)."'>Next page</a>";
					}
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>
