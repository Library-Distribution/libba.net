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
	*/

	$page_title = "Compare ERROR";
	$error = true;
	for ($i = 0; $i < 1; $i++) {
		$api = new ALD(API_URL);
		if (isset($_GET['name']) && isset($_GET['version1']) && isset($_GET['version2']))
		{
			if (!semver_validate($_GET['version1']) || !semver_validate($_GET['version2'])) {
				$error_message = 'Invalid version number specified!';
				$error_description = 'One of the two specified version numbers "' . htmlentities($_GET['version1']) . '" and "' . htmlentities($_GET['version2']) . '" is not a valid version number.';
				break;
			}
			if (semver_compare($_GET['version1'], $_GET['version2']) != -1) { #TODO: warning instead of error; redirect; link
				$error_message = 'Invalid version order specified!';
				$error_description = 'The specified two version numbers are in incorrect order: the older version must be first.';
				break;
			}

			try {
				$item1 = $api->getItem($_GET['name'], $_GET['version1']);
			} catch (HttpException $e) {
				$error_message = 'Could not retrieve specified item!';
				$error_description = 'The specified item("' . $_GET['name'] . '", version "' . $_GET['version1'] . '") could not be read. The API error message was: "' . $e->getMessage() . '".';
				break;
			}

			try {
				$item2 = $api->getItem($_GET['name'], $_GET['version2']);
			} catch (HttpException $e) {
				$error_message = 'Could not retrieve specified item!';
				$error_description = 'The specified item ("' . $_GET['name'] . '", version "' . $_GET['version2'] . '") could not be read. The API error message was: "' . $e->getMessage() . '".';
				break;
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
				$error_message = 'Could not retrieve specified item!';
				$error_description = 'The specified item (ID: "' . $_GET['id1'] . '") could not be read. The API error message was: "' . $e->getMessage() . '".';
				break;
			}

			try {
				$item2 = $api->getItemById($_GET['id2']);
			} catch (HttpException $e) {
				$error_message = 'Could not retrieve specified item!';
				$error_description = 'The specified item (ID: "' . $_GET['id2'] . '") could not be read. The API error message was: "' . $e->getMessage() . '".';
				break;
			}

			if ($item1['name'] != $item2['name']) {
				$error_message = 'Cannot compare versions of different items!';
				$error_description = 'The two specified versions belong to different items ("' . $item1['name'] . '" and "' . $item2['name'] . '"). Comparing them is currently not supported.';
				break;
			}

			$id_old = semver_compare($item1['version'], $item2['version']) == -1 ? $_GET['id1'] : $_GET['id2'];
			$version_old = semver_compare($item1['version'], $item2['version']) == -1 ? $item1['version'] : $item2['version'];
			$id_new = semver_compare($item1['version'], $item2['version']) == 1 ? $_GET['id1'] : $_GET['id2'];
			$version_new = semver_compare($item1['version'], $item2['version']) == 1 ? $item1['version'] : $item2['version'];
			$item_name = $item1['name'];
		}
		else
		{
			$error_message = 'Cannot identify items!';
			$error_description = 'The current URL cannot be used for comparison. Either name and two versions or two IDs must be specified to identify the items to compare.';
			break;
		}

		try {
			$old_item = new CompareItem($api, $id_old);
			$new_item = new CompareItem($api, $id_new);
		} catch (Exception $e) {
			$error_message = 'Cannot load items for comparison!';
			$error_description = 'An unknown error (possibly API error) occured while trying to load the items for comparison. The error message was: "' . $e->getMessage() . '".';
			break;
		}

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
		$error = false;
	}
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
			<?php
				if ($error)
				{
					require('../error.php');
				} else {
			?>
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
			<?php } ?>
		</div>
		<?php require('../footer.php'); require('../header.php'); ?>
	</body>
</html>
<?php
	require_once("../rewriter.php");
	echo rewrite();
	ob_end_flush();
?>