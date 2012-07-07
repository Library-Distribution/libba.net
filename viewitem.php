<?php
	session_start();
	if (!isset($_GET["id"]))
	{
		header("Location: index");
		exit;
	}

	require_once("sortArray.php");
	require_once("ALD.php");
	require_once("api/semver.php");

	$api = new ALD(!empty($_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");

	try
	{
		$item = $api->getItemById($_GET["id"]);
	}
	catch (HttpException $e)
	{
		die ("Failed to retrieve information about this item.<p>{$e->getMessage()}</p>");
	}
	$page_title = "\"{$item['name']}\" (v{$item['version']})";

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
				usort($versions, "semver_sort"); # sort by "version" field, following semver rules

				if (count($versions) > 1) # 1 as the version on this page is included
				{
					echo "<h3>Other versions:</h3><ul>";
					foreach ($versions AS $version)
					{
						if ($version['version'] != $item['version'])
						{
							echo "<li><a href='?id={$version['id']}'>version {$version['version']}</a></li>";
						}
					}
					echo "</ul>";
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>