<?php
ob_start();
session_start();

require_once('../config/constants.php');
require_once('../util/ALD.php');
require_once('../partials/Notice.php');
require_once('../modules/HttpException/HttpException.php');

for ($i = 0; $i < 1; $i++) {
	$error = true;
	$page_title = 'Stdlib Releases';

	$api = new ALD( API_URL );
	try {
		$releases_versions = $api->getStdlibReleases();
	} catch (HttpException $e) {
		$error_message = 'Failed to retrieve release list';
		$error_description = 'The list of releases could not be read. API error was: "' . $e->getMessage() . '"';
		break;
	}

	$releases = array();
	foreach ($releases_versions AS $release) {
		try {
			$releases[] = $api->describeStdlibRelease($release);
		} catch (HttpException $e) {
			$error_message = 'Failed to retrieve release details';
			$error_description = 'The details on release "' . $release . '" could not be read. API error was: "' . $e->getMessage() . '"';
			break;
		}
	}

	$error = false;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require('../partials/html.head.php'); ?>
		<link rel="stylesheet" type="text/css" href="style/releases/list.css"/>

		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="javascript/jquery-ui.js"></script>
		<script type="text/javascript" src="javascript/default.js"></script>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($error) {
					error($error_message, $error_description, true);
				} else {
					echo '<div id="release-list" class="js-ui-accordion">';
					foreach ($releases AS $release)  {
						echo '<div class="release-entry">',
							'<h3 class="release-header js-ui-accordion-header">', $release['release'], '</h3>',
							'<dl class="release-details">',
								'<dt>Date</dt><dd>', $release['date'], '</dd>',
								'<dt>Published</dt><dd class="release-', $release['published'] ? 'published' : 'unpublished', '">', $release['published'] ? 'yes' : 'no', '</dd>',
								'<dt>Description</dt><dd>', $release['description'] ? $release['description'] : '<em>(none given)</em>', '</dd>',
								'<dt>Link</dt><dd>&#9654; <a href="./', $release['release'], '">View details</a> &#9654;</dd>',
							'</dl></div>';
					}
					echo '</div>';
				}
			?>
		</div>
		<?php require('../partials/footer.php'); require('../partials/header.php'); ?>
	</body>
</html>
<?php
	require_once('../util/rewriter.php');
	echo rewrite();
	ob_end_flush();
?>