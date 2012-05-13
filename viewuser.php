<!DOCTYPE html>
<html>
	<?php
		$page_title = "View users";
		if (isset($_GET["user"]))
		{
			$user = $_GET["user"];
			$page_title = "User: $user";
		}
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
	?>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
	</head>
	<body>
		<?php require("header.php") ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				require("db.php");
				$db_connection = db_ensureConnection();

				if (!isset($user))
				{
					# list of users
					$start_index = $page_index * $page_itemcount;
					$db_query = "SELECT name FROM $db_table_users ORDER BY name LIMIT $start_index,$page_itemcount";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("ERROR: Failed to query for users.\n".mysql_error());

					echo "<ul>";
					while ($db_entry = mysql_fetch_object($db_result))
					{
						echo "<li><a href='?user=$db_entry->name'>$db_entry->name</a></li>";
					}
					echo "</ul>";

					if ($page_index > 0)
					{
						echo "<a class='next-previous' id='prev' href='?items=$page_itemcount&amp;page=".($page_index - 1)."'>Previous page</a>";
					}

					# check if there are more items
					$db_query = "SELECT name FROM $db_table_users ORDER BY name LIMIT ".($start_index + $page_itemcount).",1";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("ERROR: Could not query for more items.\n".mysql_error());
					if (mysql_num_rows($db_result) > 0) # if so, show the "next" link
					{
						echo "<a class='next-previous' id='next' href='?items=$page_itemcount&amp;page=".($page_index + 1)."'>Next page</a>";
					}
				}
				else
				{
					# user profile

					$db_query = "SELECT joined FROM $db_table_users WHERE name = '$user' LIMIT 1";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("ERROR: could not retrieve joined date.\n".mysql_error());
					echo "Joined: <span class='joined-date'>".(mysql_fetch_object($db_result)->joined)."</span>";

					$db_query = "SELECT name, id, version FROM $db_table_main WHERE user = '$user' AND type = 'lib' ORDER BY name, version DESC";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("ERROR: failed to query libraries.\n".mysql_error());
					$uploaded = array();

					echo "<h2>Libraries uploaded (".(mysql_num_rows($db_result)).") :</h2><ul>";
					while ($db_entry = mysql_fetch_object($db_result))
					{
						if (!in_array($db_entry->name, $uploaded))
						{
							$uploaded[] = $db_entry->name;
							echo "<li><a href='viewitem.php?id=$db_entry->id'>$db_entry->name (v$db_entry->version)</a></li>";
						}
					}
					echo "</ul>";

					$db_query = "SELECT name, id, version FROM $db_table_main WHERE user = '$user' AND type = 'app' ORDER BY name, version DESC";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("ERROR: failed to query libraries.\n".mysql_error());
					$uploaded = array();

					echo "<h2>Applications uploaded (".(mysql_num_rows($db_result)).") :</h2><ul>";
					while ($db_entry = mysql_fetch_object($db_result))
					{
						if (!in_array($db_entry->name, $uploaded))
						{
							$uploaded[] = $db_entry->name;
							echo "<li><a href='viewitem.php?id=$db_entry->id'>$db_entry->name (v$db_entry->version)</a></li>";
						}
					}
					echo "</ul>";
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>