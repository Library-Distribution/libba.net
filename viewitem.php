<?php
	session_start();
	if (!isset($_GET["id"]))
	{
		header("Location: index");
		exit;
	}
	$id = $_GET["id"];

	require_once("sortArray.php");
	require_once("ALD.php");
	require_once("api/semver.php");

	$api = new ALD(!empty($_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");
	try
	{
		$item = $api->getItemById($id);
	}
	catch (HttpException $e)
	{
		die ("Failed to retrieve information about this item.<p>{$e->getMessage()}</p>");
	}

	$page_title = "\"{$item['name']}\" (v{$item['version']})";
	$logged_in = isset($_SESSION["user"]);

	function semver_sort($a, $b)
	{
		return semver_compare($b['version'], $a['version']);
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($logged_in)
				{
					require_once("api/User.php");
					$redirect_url = urlencode($_SERVER["REQUEST_URI"]);
					if (User::hasPrivilege($_SESSION["user"], User::PRIVILEGE_REVIEW))
					{
						# insert review items
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
					}
					if (User::hasPrivilege($_SESSION["user"], User::PRIVILEGE_DEFAULT_INCLUDE) && $item['type'] == "lib" && $item['reviewed'])
					{
						# insert default_include items
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
					}
				}
			?>
			<table>
				<tr>
					<td>Uploaded by:</td>
					<td><a href="viewuser?user=<?php echo $item['user']; ?>"><?php echo $item['user']; ?></a></td>
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
				<?php echo $item['description']; ?>
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
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>