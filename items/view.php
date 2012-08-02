<?php
	session_start();
	ob_start();

	if (!isset($_GET["id"]))
	{
		header("Location: .");
		exit;
	}

	require_once("../ALD.php");
	require_once("../get_API_URL.php");
	require_once("../api/semver.php");

	$logged_in = isset($_SESSION["user"]);
	$api = new ALD(get_API_URL());
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
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("../templates/html.head.php"); ?>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($logged_in)
				{
					require_once("../privilege.php");

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
				<td><a href="users/<?php echo $item['user']['name']; ?>/profile"><?php echo $item['user']['name']; ?></a></td>
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
				require_once("../user_input.php");
				echo user_input_process($item['description']);
			?>
		</div>
		<?php

			$versions = $api->getItemList(0, "all", NULL, NULL, $item['name']);

			# remove the current item from the array
			require_once("../api/util.php");
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
					echo "<li><a href='./{$version['id']}'>version {$version['version']}</a></li>";
				}
				echo "</ul>";
			}
			?>
		</div>
		<?php require("../header.php"); require("../footer.php"); ?>
	</body>
</html>
<?php
	require_once("../rewriter.php");
	echo rewrite();
	ob_end_flush();
?>
<?php
	function semver_sort($a, $b)
	{
		return semver_compare($b['version'], $a['version']);
	}
?>