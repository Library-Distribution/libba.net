<?php
	ob_start();
	session_start();

	require_once("ALD.php");
	require_once("config/constants.php");

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION["user"]);

	if (isset($_GET["id"]))
	{
		require_once("api/db.php");
		require_once("db2.php");
		require_once('api/semver.php');

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

			require_once("user_input.php");

			$item = $api->getItemById($id);
			$page_title = $item["name"] . " (v{$item["version"]}) | Code review";

			$list = $api->getItemList(0, 'all', NULL, NULL, $item['name'], NULL, NULL, 'both', 'yes');
			usort($list, "semver_sort");
			#print_r($list);

			$j = 0;
			do {
				$diff_base = $list[$j]['version'];
				$j++;
			} while (semver_compare($item['version'], $diff_base) != 1);

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
				$comments[] = $comment;
			}

			$error = false;
		}
	}
	else
	{
		$page_title = "Unreviewed items";
		$items = $api->getItemList(0, "all", NULL, NULL, NULL, NULL, NULL, "both", "no");
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/review.css"/>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="javascript/jquery-ui.js"></script>
		<script type="text/javascript" src="javascript/comments.js"></script>
		<script type="text/javascript" src="javascript/review.js"></script>
	</head>
	<body class="pretty-ui">
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if (isset($id))
				{
					if ($error)
					{
						require("error.php");
					}
					else
					{
			?>
						<table id="review">
							<tr>
								<th>Diff:</th>
								<td><a href='items/compare/<?php echo $item['name'], '/', $diff_base, '...', $item['version']; ?>'>Compare to latest reviewed version (<?php echo $diff_base; ?>)</a></td>
							</tr>
						</table>
						<table id="review-comments">
			<?php
						foreach ($comments AS $comment)
						{
							echo "<tr><td><img alt=\"avatar\" src=\"http://gravatar.com/avatar/{$comment['user-mail']}?s=50&amp;d=mm\" class=\"comment-avatar\"/><br/><a href=\"users/{$comment["user"]}/profile\">{$comment["user"]}</a><hr/>{$comment["date"]}</td><td>" . user_input_process($comment["comment"]) . "</td></tr>";
						}
						if (!$item["reviewed"])
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
						</table>
						<a href="http://htmlpurifier.org/"><img src="http://htmlpurifier.org/live/art/powered.png" alt="Powered by HTML Purifier" border="0" /></a>
			<?php
					}
				}
				else
				{
			?>
					<div id="review-list">
						<?php
							foreach ($items AS $item)
							{
								echo '<div class="review-entry">'
									. "<h3 class='review-header'>$item[name]</h3>"
									. '<dl>'
										. "<dt>Library</dt><dd><a href='items/$item[id]'>$item[name]</a></dd>"
										. "<dt>Version</dt><dd>$item[version]</dd>"
										. "<dt>User</dt><dd><a href=\"users/{$item["user"]["name"]}/profile\">{$item["user"]["name"]}</a></dd>"
										. "<dt>Link</dt><dd>&#9654; <a href='./$item[id]'>Go to discussion thread</a> &#9654;</dd>"
									. '</dl></div>';
							}
						?>
					</div>
			<?php
				}
			?>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>
<?php
	require_once("rewriter.php");
	echo rewrite();
	ob_end_flush();
?>
<?php
	function semver_sort($a, $b)
	{
		return semver_compare($b['version'], $a['version']);
	}
?>