<?php
	ob_start();

	require_once('../ALD.php');
	require_once('../api/semver.php');
	require_once('../config/constants.php');
	require_once('../modules/diff/finediff.php');
	require_once('CompareItem.php');

	define('ACT_ADD', 1);
	define('ACT_DEL', 2);
	define('ACT_MOD', 3);

	/*
	* TODO:
	***********************
	* - check for schema differences and present them (not as text diff!)
	* - ensure correct compare order of versions
	*	If incorrect, do not silently assume correct
	*	order, but output a warning / error and a
	*	link to the correct URL. After some seconds,
	*	redirect there.
	* - tweak styling, especially for non-javascript viewers
	* - proper error handling
	*/

	$api = new ALD(API_URL);
	if (isset($_GET['name']) && isset($_GET['version1']) && isset($_GET['version2']))
	{
		try {
			$item1 = $api->getItem($_GET['name'], $_GET['version1']);
		} catch (HttpException $e) {
			# TODO: error handling
			die('failed');
		}

		try {
			$item2 = $api->getItem($_GET['name'], $_GET['version2']);
		} catch (HttpException $e) {
			# TODO: error handling
			die('failed');
		}

		$id_old = semver_compare($item1['version'], $item2['version']) == -1 ? $item1['id'] : $item2['id'];
		$version_old = semver_compare($item1['version'], $item2['version']) == -1 ? $item1['version'] : $item2['version'];
		$id_new = semver_compare($item1['version'], $item2['version']) == 1 ? $item1['id'] : $item2['id'];
		$version_new = semver_compare($item1['version'], $item2['version']) == 1 ? $item1['version'] : $item2['version'];
		$item_name = $_GET['name'];
	}
	else if (isset($_GET['id1']) && isset($_GET['id2']))
	{
		try {
			$item1 = $api->getItemById($_GET['id1']);
		} catch (HttpException $e) {
			# TODO: error handling
			die('failed');
		}

		try {
			$item2 = $api->getItemById($_GET['id2']);
		} catch (HttpException $e) {
			# TODO: error handling
			die('failed');
		}

		if ($item1['name'] != $item2['name']) {
			# TODO: error handling
			die('failed');
		}

		$id_old = semver_compare($item1['version'], $item2['version']) == -1 ? $_GET['id1'] : $_GET['id2'];
		$version_old = semver_compare($item1['version'], $item2['version']) == -1 ? $item1['version'] : $item2['version'];
		$id_new = semver_compare($item1['version'], $item2['version']) == 1 ? $_GET['id1'] : $_GET['id2'];
		$version_new = semver_compare($item1['version'], $item2['version']) == 1 ? $item1['version'] : $item2['version'];
		$item_name = $item1['name'];
	}
	else
	{
		# TODO: error handling
		die('failed');
	}

	$old_item = new CompareItem($api, $id_old);
	$new_item = new CompareItem($api, $id_new);

	$files_old = $old_item->files();
	$files_new = $new_item->files();

	$actions = array();
	foreach ($files_new AS $category => $files) # iterate over all files in new version
	{
		$actions[$category] = array();

		# check for modified or added files
		foreach ($files AS $i => $file) {
			if (!$old_item->hasFile($file)) {
				$actions[$category][$file] = array('type' => ACT_ADD);
			} else {
				$file_old = $old_item->getFile($file);
				$file_new = $new_item->getFile($file);
				if ($file_old != $file_new) {
					$ops = FineDiff::getDiffOpcodes($file_old, $file_new, FineDiff::$characterGranularity);
					$actions[$category][$file] = array('type' => ACT_MOD, 'diff' => html_entity_decode(FineDiff::renderDiffToHTMLFromOpcodes($file_old, $ops)));
				}
			}
		}

		# check for deleted files
		foreach ($files_old[$category] AS $i => $file) {
			if (!$new_item->hasFile($file)) {
				$actions[$category][$file] = array('type' => ACT_DEL);
			}
		}

		asort($actions[$category]);
	}

	$page_title = "Comparing $item_name v$version_old with v$version_new";
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require('../templates/html.head.php'); ?>
		<link type="text/css" rel="stylesheet" href="style/items/compare.css"/>

		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="javascript/jquery-ui.js"></script>
		<script type="text/javascript" src="javascript/items/compare.js"></script>
	</head>
	<body class="pretty-ui">
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<ul id="diff-steps">
				<?php
					foreach($actions AS $category => $steps) {
						if (count($steps) > 0) {
							echo '<h3>', $category, '</h3>';
						}
						foreach ($steps AS $file => $action) {
							switch ($action['type']) {
								case ACT_ADD: $prefix = 'Added:';
											$details = "File <code>$file</code> has been added to the package.";
											break;
								case ACT_DEL: $prefix = 'Deleted:';
											$details = "File <code>$file</code> has been deleted from the package.";
											break;
								default:
								case ACT_MOD: $prefix = 'Modified:';
											$details = '<div class="compare-diff">' . $action['diff'] . '</div>';
											break;
							}
							echo '<li><span class="action-summary"><span class="action-type">' . $prefix . '</span> ' . $file . '</span><div class="action-details">' . $details . '</div></li>';
						}
					}
				?>
			</ul>
		</div>
	</body>
</html>
<?php
	require_once("../rewriter.php");
	echo rewrite();
	ob_end_flush();
?>