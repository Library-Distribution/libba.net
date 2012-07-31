<?php
	session_start();

	require_once("ALD.php");

	$api = new ALD((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]) ? "https://{$_SERVER["SERVER_NAME"]}/user/maulesel/api" : "http://{$_SERVER["SERVER_NAME"]}/api");
	$logged_in = isset($_SESSION["user"]);

	if (isset($_GET["id"]))
	{
		require_once("api/db.php");
		require_once("db2.php");

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
		<link rel="stylesheet" type="text/css" href="review.css"/>
	</head>
	<body>
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
						<table id="review"><!-- todo --></table>
						<table id="review-comments">
			<?php
						foreach ($comments AS $comment)
						{
							echo "<tr><td><a href=\"viewuser?user={$comment["user"]}\">{$comment["user"]}</a><hr/>{$comment["date"]}</td><td>" . user_input_process($comment["comment"]) . "</td></tr>";
						}
						if (!$item["reviewed"])
						{
			?>
							<tr>
								<td><a href="viewuser?user=<?php echo $_SESSION["user"]; ?>">You</a><hr/>Now</td>
								<td>
									<form action="#" method="post">
										<textarea name="newcomment" style="width: 99.5%"></textarea>
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
					<table id="review-list">
						<thead>
							<tr>
								<th></th>
								<th>Library</th>
								<th>Version</th>
								<th>User</th>
							</tr>
						</thead>
						<tbody>
			<?php
						foreach ($items AS $item)
						{
							echo "<tr><td><a href=\"?id={$item["id"]}\">&gt;&gt;</a></td><td><a href=\"viewitem?name={$item["name"]}&version=latest\">{$item["name"]}</a></td><td>{$item["version"]}</td><td><a href=\"viewuser?user={$item["user"]["name"]}\">{$item["user"]["name"]}</a></td></tr>";
						}
			?>
						</tbody>
					</table>
			<?php
				}
			?>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>