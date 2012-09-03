<?php
	ob_start();
	session_start();

	require_once("user_input.php");
	require_once("api/db.php");
	require_once("db2.php");
	require_once("ALD.php");
	require_once("config/constants.php");
	require_once("privilege.php");

	$db_connection = db_ensure_connection();

	for ($i = 0; $i < 1; $i++)
	{
		$api = new ALD( API_URL );
		$error = true; # assume error here, reset on success

		if (isset($_GET["id"]))
		{
			$id = mysql_real_escape_string($_GET["id"], $db_connection);
			$logged_in = isset($_SESSION["userID"]);
			$can_close = $logged_in && hasPrivilege($_SESSION["privileges"], PRIVILEGE_STDLIB);

			if (!empty($_POST) && $logged_in)
			{
				if (isset($_POST["newcomment"]))
				{
					if (isset($_POST["vote"]))
					{
						$vote = (int)(mysql_real_escape_string($_POST["vote"]));
						if (in_array($vote, array(-1, 0, 1)))
						{
							$db_query = "SELECT COUNT(*) FROM $db_table_candidate_comments WHERE id = '$id' AND vote != '0' AND user = UNHEX('{$_SESSION["userID"]}')";
							$db_result = mysql_query($db_query, $db_connection);
							if (!$db_result)
							{
								$error_message = "Failed to get previous votes: MySQL error";
								$error_description = "Could not check if current user has already voted. MySQL error was: '" . mysql_error() . "'";
								break;
							}
							$can_vote = mysql_fetch_object($db_result)->{'COUNT(*)'} == 0; # set to false if there's already a comment by the current user with a vote

							if (!$can_vote)
								$vote = 0;
						}
						else
							$vote = 0;
					}

					$db_query = "INSERT INTO $db_table_candidate_comments (id, user, comment, vote) VALUES ($id, UNHEX('{$_SESSION["userID"]}'), '" . mysql_real_escape_string($_POST["newcomment"]) . "', '" . $vote . "')";
					$db_result = mysql_query($db_query, $db_connection);
					if (!$db_result)
					{
						$error_message = "Failed to save comment: MySQL error";
						$error_description = "Could not save your last comment on this thread. MySQL error was: '" . mysql_error() . "'";
						break;
					}
				}
				else if (isset($_POST["accept"]) || isset($_POST["reject"]))
				{
					if ($can_close)
					{
						$db_query = "UPDATE $db_table_candidates Set closed = '1', closed-by = UNHEX('{$_SESSION["userID"]}'), closed-date = NOW(), closed-comment = '" . mysql_real_escape_string($_POST["closecomment"]) . "' WHERE id = '$id'";
						$db_result = mysql_query($db_query, $db_connection);
						if (!$db_result)
						{
							$error_message = "Failed to close this thread: MySQL error";
							$error_description = "Could not close the thread. MySQL error was: '" . mysql_error() . "'";
							break;
						}

						$db_query = "UPDATE $db_table_main Set default_include = '1' WHERE id = UNHEX('')"; # todo
						# TODO
					}
				}
				header("Location: " . $_SERVER["REQUEST_URI"]); # reload to clear POST data and avoid repost of comment
			}

			$db_query = "SELECT *, HEX(libid), HEX(userid), HEX(`closed-by`) FROM $db_table_candidates WHERE id = '$id'";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to retrieve thread: MySQL error";
				$error_description = "Could not retrieve data on this thread. MySQL error was: '" . mysql_error() . "'";
				break;
			}
			if (mysql_num_rows($db_result) != 1)
			{
				$error_message = "Failed to retrieve thread: not found";
				$error_description = "Could not find this thread. Most likely, the URL is incorrect.";
				break;
			}
			$candidate = mysql_fetch_assoc($db_result);

			$lib = $api->getItemById($candidate["HEX(libid)"]);
			$candidate["libname"] = $lib["name"];
			$candidate["libversion"] = $lib["version"];
			$temp = $api->getUserById($candidate["HEX(userid)"]);
			$candidate["username"] = $temp["name"];
			if ($candidate["closed"])
			{
				$temp = $api->getUserById($candidate["HEX(`closed-by`)"]);
				$candidate["closed-by"] = $temp["name"];
			}

			$comments = array();
			$db_query = "SELECT *, HEX(user) FROM $db_table_candidate_comments WHERE id = '$id'";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to retrieve comments: MySQL error";
				$error_description = "Could not read the comments on this thread. MySQL error was: '" . mysql_error() . "'";
				break;
			}
			while ($comment = mysql_fetch_assoc($db_result))
			{
				$temp = $api->getUserById($comment["HEX(user)"]);
				$comment["user"] = $temp["name"];
				$comment["user-mail"] = $temp["mail"];
				$comments[] = $comment;
			}

			$db_query = "SELECT COUNT(*) FROM $db_table_candidate_comments WHERE id = '$id' AND vote > '0'"; # get upvote count
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to retrieve upvote count: MySQL error";
				$error_description = "The number of upvotes could not be read. MySQL error was: '" . mysql_error() . "'";
				break;
			}
			$up_vote_count = mysql_fetch_object($db_result)->{'COUNT(*)'};

			$db_query = "SELECT COUNT(*) FROM $db_table_candidate_comments WHERE id = '$id' AND vote < '0'"; # get downvote count
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to retrieve downvote count: MySQL error";
				$error_description = "The number of downvotes could not be read. MySQL error was: '" . mysql_error() . "'";
				break;
			}
			$down_vote_count = mysql_fetch_object($db_result)->{'COUNT(*)'};

			$total_vote_count = $up_vote_count - $down_vote_count;

			if ($logged_in)
			{
				$db_query = "SELECT COUNT(*) FROM $db_table_candidate_comments WHERE id = '$id' AND vote != '0' AND user = UNHEX('{$_SESSION["userID"]}')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					$error_message = "Failed to get previous votes: MySQL error";
					$error_description = "Could not check if current user has already voted. MySQL error was: '" . mysql_error() . "'";
					break;
				}
				$can_vote = mysql_fetch_object($db_result)->{'COUNT(*)'} == 0; # set to false if there's already a comment by the current user with a vote
			}

			$page_title = ($candidate["closed"] ? "closed: " : "") . $candidate["libname"] . " v" . $candidate["libversion"] . " | Candidate for stdlib";
		}
		else
		{
			$page_title = "Candidates for the standard library";

			$db_cond = "closed != '1'";
			if (isset($_GET["mode"]))
			{
				if (strtolower($_GET["mode"]) == "closed")
				{
					$db_cond = "closed = '1'";
					$page_title .= " (closed)";
				}
				else if (strtolower($_GET["mode"]) == "all")
				{
					$db_cond = "'1' = '1'";
				}
			}

			$db_query = "SELECT id, HEX(libid), HEX(userid), date, closed FROM $db_table_candidates WHERE $db_cond";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to retrieve list of candidates: MySQL error";
				$error_description = "The list of candidates could not be read. MySQL error was: '" . mysql_error() . "'";
				break;
			}

			$candidates = array();
			while ($candidate = mysql_fetch_assoc($db_result))
			{
				$lib = $api->getItemById($candidate["HEX(libid)"]);
				$candidate["lib-name"] = $lib["name"];
				$candidate["lib-version"] = $lib["version"];

				$temp = $api->getUserById($candidate["HEX(userid)"]);
				$candidate["user"] = $temp["name"];

				$candidates[] = $candidate;
			}
		}
		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/candidate.css"/>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					require("error.php");
				}
				else if (isset($id))
				{
			?>
					<table id="candidate">
						<tr>
							<td>Library:</td>
							<td><a href="items/<?php echo $candidate["HEX(libid)"]; ?>"><?php echo $candidate["libname"]; ?> (v<?php echo $candidate["libversion"]; ?>)</a></td>
						</tr>
						<tr>
							<td>User:</td>
							<td><a href="users/<?php echo $candidate["username"]; ?>/profile"><?php echo $candidate["username"]; ?></a></td>
						</tr>
						<tr>
							<td>Applied:</td>
							<td><?php echo $candidate["date"]; ?></td>
						</tr>
						<tr>
							<td colspan="2" id="candidate-text"><?php echo user_input_process($candidate["text"]); ?></td>
						</tr>
					</table>
					<div id="votes"><div class="vote upvote">+<?php echo $up_vote_count; ?></div><div class="vote downvote">-<?php echo $down_vote_count; ?></div><div class="vote"><?php echo ($total_vote_count > 0 ? "+" : "-") . $total_vote_count; ?> votes</div></div>
					<h2>Comments</h2>
					<table id="candidate-comments">
						<?php
							foreach ($comments AS $comment)
							{
								echo "<tr><td><img alt=\"avatar\" src=\"http://gravatar.com/avatar/{$comment['user-mail']}?s=50&amp;d=mm\" class=\"comment-avatar\"/><br/><a href=\"users/{$comment["user"]}/profile\">{$comment["user"]}</a><hr/>{$comment["date"]}</td>"
									. "<td>" . user_input_process($comment["comment"]) . (!empty($comment["vote"]) ? "<div class=\"vote\" style=\"float: right\">+1</div>" : "") . "</td></tr>";
							}
							if (!$candidate["closed"])
							{
								if ($logged_in)
								{
						?>
									<tr>
										<td><a href="users/<?php echo $_SESSION["user"]; ?>/profile">You</a><hr/>Now</td>
										<td>
											<form action="#" method="post">
												<textarea name="newcomment" style="width: 99.5%"></textarea>
						<?php
												if ($can_vote)
												{
						?>
													<div class="vote-option"><input type="radio" name="vote" value="-1"> &dArr; Vote down &dArr; </input></div>
													<div class="vote-option"><input type="radio" name="vote" value="0" checked="checked">&lArr; neutral &rArr; </input></div>
													<div class="vote-option"><input type="radio" name="vote" value="1"> &uArr; Vote up &uArr; </input></div>
						<?php
												}
						?>
												<input type="submit" value="Submit" style="float: right"/>
											</form>
										</td>
									</tr>
						<?php
									if ($can_close)
									{
						?>
										<tr>
											<td><a href="users/teams/stdlib">Stdlib team</a></td>
											<td>
												<form action="#" method="post" style="text-align: center">
													<textarea name="closecomment" style="width: 99.5%"></textarea>
													<input style="width: 49%; display: inline-block" type="submit" value="accept" name="accept"/>
													<input style="width: 49%; display: inline-block" type="submit" value="reject" name="reject"/>
												</form>
											</td>
										</tr>
						<?php
									}
								}
							}
							else
							{
								echo "<tr><td><a href=\"users/{$candidate["closed-by"]}/profile\">{$candidate["closed-by"]}</a><hr/>{$candidate["closed-date"]}</td>"
									. "<td id=\"close-comment\" class=\"" . ( /* todo: get if included in stdlib or not */ "") . "\">" . user_input_process($candidate["closed-comment"]) . "</td></tr>";
								/*
								if ($can_close && !$in_standard)
								{
									reopen for discussion
								}
								*/
							}
						?>
					</table>
					<a href="http://htmlpurifier.org/"><img src="http://htmlpurifier.org/live/art/powered.png" alt="Powered by HTML Purifier" border="0" /></a>
			<?php
				}
				else
				{
			?>
					<table id="candidate-list">
						<thead>
							<tr>
								<th></th>
								<th>Library</th>
								<th>User</th>
								<th>Date</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach ($candidates AS $cand)
							{
								echo "<tr><td><a href=\"./{$cand["id"]}\">&gt;&gt;</a></td><td><a href=\"items/{$cand["HEX(libid)"]}\">{$cand["lib-name"]} (v{$cand["lib-version"]})</a></td><td><a href=\"users/{$cand["user"]}/profile\">{$cand["user"]}</a></td><td>{$cand["date"]}</td><td class=\"" . ($cand["closed"] ? "cand-closed" : "cand-open") . "\">" . ($cand["closed"] ? "closed" : "open") . "</td></tr>";
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
<?php
	require_once("rewriter.php");
	echo rewrite();
	ob_end_flush();
?>