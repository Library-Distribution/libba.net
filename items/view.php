<?php
	session_start();
	ob_start();

	if (!isset($_GET["id"]) && (!isset($_GET["name"]) || !isset($_GET["version"])))
	{
		header("Location: .");
		exit;
	}

	require_once("../ALD.php");
	require_once("../config/constants.php");
	require_once("../api/semver.php");

	$logged_in = isset($_SESSION["user"]);
	$api = new ALD( API_URL );

	try
	{
		if (isset($_GET["id"]))
		{
			$id = $_GET["id"];
			$item = $api->getItemById($id);
		}
		else
		{
			$item = $api->getItem($_GET["name"], $_GET["version"]);
			$id = $item["id"];
		}
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
		<link rel="stylesheet" type="text/css" href="style/items/view.css"/>
	</head>
	<body class="pretty-ui">
		<h1 id="page-title" class="<?php echo $item['type']; ?>"><?php echo $page_title; ?></h1>
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
		<table id="item-details">
			<tr>
				<th>Uploaded by:</th>
				<td><a href="users/<?php echo $item['user']['name']; ?>/profile"><?php echo $item['user']['name']; ?></a></td>
			</tr>
			<tr>
				<th>Uploaded:</th>
				<td><?php echo $item['uploaded']; ?></td>
			</tr>
			<tr>
				<th>Tags:</th>
				<td>
					<?php
						foreach ($item['tags'] AS $tag)
						{
							echo "<a href='./?tags=$tag'>$tag</a> ";
						}
					?>
				</td>
			</tr>
			<tr>
				<th>Reviewed:</th>
				<td><?php echo "<span style=\"font-weight: bolder; color: " . ($item['reviewed'] ? "green\">Yes" : "red\">No") . "</span>"; ?></td>
			</tr>
		</table>
		<h3 id="item-descr-title">Description</h3>
		<p id="item-descr">
			<div class='markdown'>
			<?php
				require_once("../user_input.php");
				echo user_input_process($item['description']);
			?>
			</div>
		</p>
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
					$comparison = semver_compare($item['version'], $version['version']) < 0 ? $item['version'] . '...' . $version['version'] : $version['version'] . '...' . $item['version'];
					echo "<li><a href='./{$version['id']}'>version {$version['version']}</a><a class='compare-link' title='compare against version $version[version]' href='./compare/$version[name]/$comparison'/></a></li>";
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