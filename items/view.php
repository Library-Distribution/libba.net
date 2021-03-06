<?php
	session_start();
	ob_start();

	if (!isset($_GET["id"]) && (!isset($_GET["name"]) || !isset($_GET["version"])))
	{
		header("Location: .");
		exit;
	}

	require_once("../util/ALD.php");
	require_once("../config/constants.php");
	require_once('../modules/semver/semver.php');
	require_once('../modules/HttpException/HttpException.php');
	require_once('../partials/Notice.php');

	$logged_in = isset($_SESSION["user"]);
	$api = new ALD( API_URL );
	$error = false;

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
		$page_title = "\"{$item['name']}\" (v{$item['version']})";
	}
	catch (HttpException $e)
	{
		$error = true;
		$error_message = 'Could not retrieve specified item!';
		$error_description = 'Failed to retrieve item to display. The API error message was: "' . $e->getMessage() . '"';
		$page_title = 'ERROR retrieving item';
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("../partials/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/items/view.css"/>
	</head>
	<body>
		<h1 id="page-title" class="<?php echo $item['type']; ?> side-image"><?php echo $page_title; ?></h1>
		<div id="page-content">
		<?php
		if ($error) {
			error($error_message, $error_description, true);
		}
		else {
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
				<tr>
					<th>Download Count:</th>
					<td><?php echo $item['downloads']; ?></td>
				</tr>
				<tr>
					<th>Average Rating by Users:</th>
					<td><?php echo $item['rating']; ?></td>
				</tr>
			</table>
			<h3 id="item-descr-title">Description</h3>
			<p id="item-descr">
				<div class='markdown'>
				<?php
					require_once("../util/user_input.php");
					echo user_input_process($item['description']);
				?>
				</div>
			</p>
		<?php

			$versions = $api->getItemList(0, "all", NULL, NULL, $item['name']);

			# remove the current item from the array
			require_once("../util/searchSubArray.php");
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
		}
		?>
		</div>
		<?php require("../partials/header.php"); require("../partials/footer.php"); ?>
	</body>
</html>
<?php
	require_once("../util/rewriter.php");
	echo rewrite();
	ob_end_flush();
?>
<?php
	function semver_sort($a, $b)
	{
		return semver_compare($b['version'], $a['version']);
	}
?>