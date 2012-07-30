<?php
	session_start();

	require_once("ALD.php");
	require_once("sortArray.php");

	$logged_in = isset($_SESSION["user"]);
	$api = new ALD(!empty($_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");

	if (isset($_GET["id"])) # output an item
	{
		require_once("api/semver.php");

		$id = $_GET["id"];
		try
		{
			$item = $api->getItemById($id);
		}
		catch (HttpException $e)
		{
			die ("Failed to retrieve information about this item.<p>{$e->getMessage()}</p>");
		}

		$page_title = "\"{$item['name']}\" (v{$item['version']})";
	}
	else # output a list of items
	{
		$page_title = "Browse ";

		if ($type = (!empty($_GET["type"]) && in_array(strtolower($_GET["type"]), array("app", "lib"))) ? strtolower($_GET["type"]) : NULL)
		{
			$page_title .= ($type == "app") ? "applications" : ($type == "lib" ? "libraries" : "libraries and applications");
		}
		else # probably remove unknown type and reload?
		{
			$page_title .= "libraries and applications";
		}

		$user = !empty($_GET["user"]) ? $_GET["user"] : NULL
			AND $page_title .= " by $user";
		$stdlib = !empty($_GET["stdlib"]) ? $_GET["stdlib"] : "both"
			AND $page_title .= !empty($_GET["stdlib"]) ? " (lib standard)" : "";
		$tags = isset($_GET["tags"]) ? explode("|", $_GET["tags"]) : NULL
			AND $page_title .= " (tags: " . implode($tags, ", ") . ")";

		$page_index = !empty($_GET["page"]) ? (int)$_GET["page"] : 0;
		$page_itemcount = !empty($_GET["items"]) ? (int)$_GET["items"] : 20;
		$start_index = $page_index * $page_itemcount;

		$items = $api->getItemList($start_index, $page_itemcount + 1, $type, $user, NULL, $tags, "latest", $stdlib);
		$items = sortArray($items, "name");
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php
			require("templates/html.head.php");

			if (!isset($id)) {
		?>
				<link rel="stylesheet" type="text/css" href="viewitem.list.css"/>
		<?php } ?>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (!isset($id))
				{
					$last_letter = "";
					$i = 0;
					foreach ($items as $item)
					{
						$i++;
						if ($i > $page_itemcount)
						{
							break;
						}

						$current_letter = strtoupper(substr($item['name'], 0, 1));
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
						echo "<li id='item{$item['id']}'><a class='item' href='?id={$item['id']}'>{$item['name']}</a> (v{$item['version']}) by <a class='userlink' href='viewuser?user={$item['user']['name']}'>{$item['user']['name']}</a></li>";
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
				}
				else
				{
					if ($logged_in)
					{
						require_once("privilege.php");

						$redirect_url = urlencode($_SERVER["REQUEST_URI"]);
						if (hasPrivilege($_SESSION["privileges"], PRIVILEGE_REVIEW))
						{
							# insert review items
							/*
							echo "<div class=\"menu\">Code Review<ul class=\"admin-menu\">";
							if ($item['reviewed'])
							{
								echo "<a href='moderator-action.php?id=$id&amp;action=review&amp;value=0&amp;return_error=true&amp;redirect=$redirect_url'><li>Mark as <span style=\"font-weight: bold;\">unreviewed</span></li></a>";
							}
							else
							{
								echo "<a href='moderator-action.php?id=$id&amp;action=review&amp;value=1&amp;return_error=true&amp;redirect=$redirect_url'><li>Mark as <span style=\"font-weight: bold; color: green\">secure and stable</span></li></a>";
							}
							echo "<a href='moderator-action.php?id=$id&amp;action=review&amp;value=-1&amp;return_error=true&amp;redirect=$redirect_url'><li>Mark as <span style=\"font-weight: bold; color: red\">unsecure or unstable</span></li></a>";
							echo "</ul></div>";
							*/
						}
						if (hasPrivilege($_SESSION["privileges"], PRIVILEGE_STDLIB) && $item['type'] == "lib" && $item['reviewed'])
						{
							# insert default_include items
							/*
							echo "<div class=\"menu\">Library standard<ul class=\"admin-menu\">";
							if ($item['default'])
							{
								echo "<a href='moderator-action.php?id=$id&amp;action=default&amp;value=0&amp;return_error=true&amp;redirect=$redirect_url'><li><span style=\"font-weight: bold; color: red\">Remove</span></li></a>";
							}
							else
							{
								echo "<a href='moderator-action.php?id=$id&amp;action=default&amp;value=1&amp;return_error=true&amp;redirect=$redirect_url'><li><span style=\"font-weight: bold; color: green\">Add</span></li></a>";
							}
							echo "</ul></div>";
							*/
						}
					}
			?>
			<table>
				<tr>
					<td>Uploaded by:</td>
					<td><a href="viewuser?user=<?php echo $item['user']['name']; ?>"><?php echo $item['user']['name']; ?></a></td>
				</tr>
				<tr>
					<td>Uploaded:</td>
					<td><?php echo $item['uploaded']; ?></td>
				</tr>
				<tr>
					<td>Tags:</td>
					<td>
						<?php
							foreach ($item['tags'] AS $tag)
							{
								echo "<a href='index?tags=$tag'>$tag</a> ";
							}
						?>
					</td>
				</tr>
				<tr>
					<td>Reviewed:</td>
					<td><?php echo "<span style=\"font-weight: bolder; color: " . ($item['reviewed'] ? "green\">Yes" : "red\">No") . "</span>"; ?></td>
				</tr>
			</table>
			<h3>Description</h3>
			<div>
				<?php
					require_once("markdown/markdown.php");
					require_once("smartypants/smartypants.php");
					echo SmartyPants(Markdown($item['description']));
				?>
			</div>
			<?php

				$versions = $api->getItemList(0, "all", NULL, NULL, $item['name']);

				# remove the current item from the array
				require_once("api/util.php");
				$index = searchSubArray($versions, array("id" => $item["id"]));
				if ($index !== NULL)
				{
					unset($versions[$index]);
				}

				usort($versions, "semver_sort"); # sort by "version" field, following semver rules

				if (count($versions) > 0)
				{
					echo "<h3>Other versions:</h3><ul>";
					foreach ($versions AS $version)
					{
						echo "<li><a href='?id={$version['id']}'>version {$version['version']}</a></li>";
					}
					echo "</ul>";
				}
			}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>
<?php
	function semver_sort($a, $b)
	{
		return semver_compare($b['version'], $a['version']);
	}
?>