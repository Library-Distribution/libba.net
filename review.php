<?php
	ob_start();
	session_start();

	require_once("util/ALD.php");
	require_once("config/constants.php");
	require_once('partials/Notice.php');

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION["user"]);

	if (isset($_GET["id"]))
	{
		require_once("util/db.php");
		require_once('modules/semver/semver.php');
		require_once('util/get_privilege_symbols.php');

		$db_connection = db_ensure_connection();
		$id = mysql_real_escape_string($_GET["id"], $db_connection);
		$error = true;

		for ($i = 0; $i < 1; $i++)
		{
			if (isset($_POST["newcomment"]))
			{
				if ($logged_in)
				{
					$db_query = "INSERT INTO $db_table_review_comments (id, user, comment) VALUES (UNHEX('$id'), UNHEX('{$_SESSION["userID"]}'), '" . mysql_real_escape_string($_POST["newcomment"]) . "')";
					$db_result = mysql_query($db_query, $db_connection);
					if (!$db_result)
					{
						$error_message = "Failed to post comment: MySQL error";
						$error_description = "Could not insert new comment. MySQL error was: '" . mysql_error() . "'";
						break;
					}
				}
				header("Location: " . $_SERVER["REQUEST_URI"]);
			}

			require_once("util/user_input.php");

			$item = $api->getItemById($id);
			$page_title = $item["name"] . " (v{$item["version"]}) | Code review";

			$list = $api->getItemList(0, 'all', NULL, NULL, $item['name'], NULL, NULL, 'both', 'yes');
			usort($list, "semver_sort");

			for ($j = 0; $j < count($list); $j++)
			{
				if (semver_compare($item['version'], $list[$j]['version']) == 1) {
					$diff_base = $list[$j]['version'];
					break;
				}
			}

			$db_query = "SELECT HEX(user), comment, date FROM $db_table_review_comments WHERE id = UNHEX('$id')";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to read comments: MySQL error";
				$error_description = "Comments could not be read from database. MySQL error was: '" . mysql_error() . "'";
				break;
			}

			$comments = array();
			while ($comment = mysql_fetch_assoc($db_result))
			{
				$temp = $api->getUserById($comment["HEX(user)"]);
				$comment["user"] = $temp["name"];
				$comment["user-mail"] = $temp["mail-md5"];
				$comment['user-privilege'] = $temp['privileges'];
				$comments[] = $comment;
			}

			$error = false;
		}
	}
	else
	{
		$page_title = "Unreviewed items";
		$items = $api->getItemList(0, "all", NULL, NULL, NULL, NULL, NULL, "both", "no");
		foreach ($items AS &$item) {
			$item_data = $api->getItemById($item['id']);
			$item['user'] = $item_data['user']['name'];
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("partials/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/reviews/<?php echo isset($id) ? 'view' : 'list'; ?>.css"/>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
		<script type="text/javascript" src="javascript/default.js"></script>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (isset($error) && $error)
				{
					error($error_message, $error_description, true);
				}
				else if (isset($id))
				{
		?>
					<table id="review">
						<tbody>
							<tr>
								<th><?php echo $item['type'] == 'lib' ? 'Library' : 'App'; ?>:</th>
								<td><a href="items/<?php echo $item['id']; ?>"><?php echo $item['name']; ?> (v<?php echo $item['version']; ?>)</a></td>
							</tr>
							<tr>
								<th>User:</th>
								<td><a href="users/<?php echo $item['user']['name']; ?>/profile"><?php echo $item['user']['name']; ?></a></td>
							</tr>
							<tr>
								<th>Uploaded:</th>
								<td><?php echo $item['uploaded']; ?></td>
							</tr>
							<?php if (isset($diff_base)) { ?>
							<tr>
								<th>Diff:</th>
								<td><a class='compare' href='items/compare/<?php echo $item['name'], '/', $diff_base, '...', $item['version']; ?>'>compare to latest reviewed version (<?php echo $diff_base; ?>)</a></td>
							</tr>
							<?php } ?>
							<tr>
								<td colspan="2" class="topic-details"><div class='markdown'><?php echo user_input_process($item['description']); ?></div></td>
							</tr>
						</tbody>
					</table>
					<h2>Comments</h2>
					<table id="review-comments">
						<tbody>
				<?php
							foreach ($comments AS $comment)
							{
								$symbols = get_privilege_symbols($comment['user-privilege']);
								echo "<tr><td><img alt=\"avatar\" src=\"http://gravatar.com/avatar/{$comment['user-mail']}?s=50&amp;d=mm\" class=\"comment-avatar\"/><br/><a href=\"users/{$comment["user"]}/profile\">{$comment["user"]}</a>$symbols<hr/>{$comment["date"]}</td><td><div class='markdown'>" . user_input_process($comment["comment"]) . "</div></td></tr>";
							}
							if (!$item["reviewed"] && $logged_in)
							{
				?>
								<tr>
									<td><a href="users/<?php echo $_SESSION["user"]; ?>/profile">You</a><hr/>Now</td>
									<td>
										<form action="#" method="post">
											<textarea class="preview-source" name="newcomment" style="width: 99.5%" placeholder="Enter your comment..."></textarea>
											<input type="submit" value="Submit" style="float: right"/>
										</form>
									</td>
								</tr>
				<?php
							}
				?>
						</tbody>
					</table>
					<a href="http://htmlpurifier.org/"><img src="http://htmlpurifier.org/live/art/powered.png" alt="Powered by HTML Purifier" border="0" /></a>
		<?php
				}
				else
				{
			?>
					<div id="review-list" class="js-ui-accordion">
						<?php
							foreach ($items AS $item)
							{
								echo '<div class="review-entry">'
									. "<h3 class='review-header js-ui-accordion-header'>$item[name]</h3>"
									. '<dl>'
										. "<dt>Library</dt><dd><a href='items/$item[id]'>$item[name]</a></dd>"
										. "<dt>Version</dt><dd>$item[version]</dd>"
										. '<dt>User</dt><dd><a href="users/', $item['user'], '/profile">', $item['user'], '</a></dd>'
										. "<dt>Link</dt><dd>&#9654; <a href='./$item[id]'>Go to discussion thread</a> &#9654;</dd>"
									. '</dl></div>';
							}
						?>
					</div>
			<?php
				}
			?>
		</div>
		<?php require("partials/footer.php"); require("partials/header.php"); ?>
	</body>
</html>
<?php
	require_once("util/rewriter.php");
	echo rewrite();
	ob_end_flush();
?>
<?php
	function semver_sort($a, $b)
	{
		return semver_compare($b['version'], $a['version']);
	}
?>