<?php
	$body_char_count = 255; # config

	ob_start();
	session_start();

	if (!isset($_GET["user"]))
	{
		header("Location: .");
	}

	require_once("../sortArray.php");
	require_once("../ALD.php");
	require_once("../config/constants.php");
	require_once("../user_input.php");
	require_once("../privilege.php");
	require_once("../api/db.php");
	require_once("../db2.php");

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION["user"]);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		$user = $_GET["user"];
		try
		{
			$user_data = $api->getUser($user);
		}
		catch (HttpException $e)
		{
			$error_message = "Failed to retrieve user: API error";
			$error_description = "User data could not be retrieved. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
			break;
		}
		$page_title = $user;
		$db_connection = db_ensure_connection();

		$activity_item_count = !empty($_GET["items"]) ? strtolower($_GET["items"]) : 15;
		$db_limit = $activity_item_count == "all" ? "" : " LIMIT $activity_item_count";

		$activity = array();
		$retrieved_items = array();

		# joined
		$activity[] = array("header" => "$user joined libba.net",
							"text" => "Welcome to libba.net, $user! If you have any questions, consult the <a href=\"help\">help</a> or contact us!",
							"image" => "images/activity/joined.png",
							"date" => $user_data["joined"],
							"link" => "./../$user/profile");

		# get items uploaded
		try
		{
			$items = $api->getItemList(0, "all", NULL, $user);
		}
		catch (HttpException $e)
		{
			$error_message = "Failed to retrieve activity: API error";
			$error_description = "Could not get items uploaded. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
			break;
		}
		foreach ($items AS $item)
		{
			$id = $item["id"];
			$retrieved_items[$id] = $api->getItemById($id);
			$activity[] = array("header" => "$user uploaded <a href=\"items/$id\">{$item["name"]} (v{$item["version"]})</a>",
								"text" => $retrieved_items[$id]["description"],
								"image" => "images/activity/upload.png",
								"date" => $retrieved_items[$id]["uploaded"],
								"link" => "items/$id");
		}

		# get review comments
		$db_query = "SELECT HEX(id), comment, date FROM $db_table_review_comments WHERE user = UNHEX('{$user_data["id"]}') ORDER BY date DESC $db_limit";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = "Failed to retrieve activity: MySQL error";
			$error_description = "Review comments could not be read. MySQL error was: '" . mysql_error() . "'";
			break;
		}
		while ($comment = mysql_fetch_assoc($db_result))
		{
			$id = $comment["HEX(id)"];
			$item = isset($retrieved_items[$id])
						? $retrieved_items[$id]
						: ($retrieved_items[$id] = $api->getItemById($id));
			$activity[] = array("header" => "$user commented on <a href=\"reviews/$id\">Code Review for {$item["name"]} v{$item["version"]}</a>",
								"text" => $comment["comment"],
								"image" => "images/activity/review-comment.png",
								"date" => $comment["date"],
								"link" => "reviews/$id"); # todo: anchor for comment
		}

		if (hasPrivilege($user_data["privileges"], PRIVILEGE_REVIEW))
		{
			# get reviews closed by user
			# todo!
		}

		$retrieved_candidatures = array();

		# get candidatures opened and closed
		$db_query = "SELECT *, HEX(libid), HEX(userid), HEX(`closed-by`) FROM $db_table_candidatures WHERE userid = UNHEX('{$user_data["id"]}') ORDER BY date DESC $db_limit";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = "Failed to retrieve activity: MySQL error";
			$error_description = "Failed to retrieve candidatures. MySQL error was: '" . mysql_error() . "'";
			break;
		}
		while ($candidature = mysql_fetch_assoc($db_result))
		{
			$retrieved_candidatures[$candidature["id"]] = $candidature; # save data for further use

			$id = $candidature["HEX(libid)"];
			$item = isset($retrieved_items[$id])
						? $retrieved_items[$id]
						: ($retrieved_items[$id] = $api->getItemById($id));
			$activity[] = array("header" => "$user proposed <a href=\"items/$id\">{$item["name"]} (v{$item["version"]})</a> for the stdlib",
								"text" => $candidature["text"],
								"image" => "images/activity/candidature.png",
								"date" => $candidature["date"],
								"link" => "candidatures/" . $candidature["id"]);

			if ($candidature["closed"])
			{
				$user = $api->getUserById($candidature["HEX(`closed-by`)"]);
				$accepted = $item["default"] ? "accepted" : "rejected";
				$activity[] = array("header" => "The stdlib candidature for {$item["name"]} v{$item["version"]} has been $accepted by <a href=\"users/{$user["name"]}/profile\">{$user["name"]}</a>",
									"text" => $candidature["closed-comment"],
									"image" => "images/activity/candidature-$accepted.png",
									"date" => $candidature["closed-date"],
									"link" => "candidatures/{$candidature["id"]}#closecomment");
			}
		}

		if (hasPrivilege($user_data["privileges"], PRIVILEGE_STDLIB))
		{
			# get candidatures closed by this user
			$db_query = "SELECT *, HEX(libid), HEX(userid), HEX(`closed-by`) FROM $db_table_candidatures WHERE `closed-by` = UNHEX('{$user_data["id"]}') ORDER BY date DESC $db_limit";
			$db_result = mysql_query($db_query, $db_connection);
			if (!$db_result)
			{
				$error_message = "Failed to retrieve activity: MySQL error";
				$error_description = "Could not read candidatures closed by $user. MySQL error was: '" . mysql_error() . "'";
				break;
			}
			while ($candidature = mysql_fetch_assoc($db_result))
			{
				#$candidatures_closed[] = $candidature; # TODO
				$retrieved_candidatures[$candidature["id"]] = $candidature;
			}
		}

		# get candidature comments
		$db_query = "SELECT id, comment, date, vote FROM $db_table_candidature_comments WHERE user = UNHEX('{$user_data["id"]}') ORDER BY date DESC $db_limit";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = "Failed to retrieve activity: MySQL error";
			$error_descrition = "Candidature comments could not be read. MySQL error was: '" . mysql_error() . "'";
			break;
		}
		while ($comment = mysql_fetch_assoc($db_result))
		{
			$id = $comment["id"];

			$candidature = isset($retrieved_candidatures[$id])
						? $retrieved_candidatures[$id]
						: ($retrieved_candidatures[$id] = getCandidature($id, $error_message, $error_description));
			if (!$candidature)
			{
				break;
			}
			$item_id = $candidature["HEX(libid)"];
			$item = isset($retrieved_items[$id])
						? $retrieved_items[$id]
						: ($retrieved_items[$id] = $api->getItemById($item_id));

			$activity[] = array("header" => "$user commented on <a href=\"candidatures/$id\">{$item["name"]} v{$item["version"]} - Stdlib candidature</a>",
								"text" => $comment["comment"],
								"image" => "images/activity/candidature-comment.png",
								"date" => $comment["date"],
								"link" => "candidatures/$id");
		}

		$activity = sortArray($activity, array("date" => true));
		if ($activity_item_count != "all")
		{
			$activity = array_slice($activity, 0, $activity_item_count);
		}

		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("../templates/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/users/general.css"/>
		<link rel="stylesheet" type="text/css" href="style/users/activity.css"/>
	</head>
	<body>
		<h1 id="page-title">
			<?php
				echo "<img alt=\"$user's avatar\" id=\"user-gravatar\" src=\"http://gravatar.com/avatar/{$user_data['mail']}?s=50&amp;d=mm\"/>";
				echo $page_title;
			?>
		</h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					require("../error.php");
				}
				else
				{
					foreach ($activity AS $item)
					{
						$text = user_input_clean(strlen($item["text"]) <= $body_char_count
									? $item["text"]
									: substr($item["text"], 0, $body_char_count) . "...");

						echo "<div class=\"activity-item\">"
								. "<div class=\"activity-header\">"
									. "<a href=\"{$item["link"]}\">"
										. "<img class=\"activity-icon\" alt=\"activity icon\" src=\"{$item["image"]}\"/>"
									. "</a>"
									. $item["header"]
								. "</div><hr/>"
								. "<div class=\"activity-body user-markup\">$text</div>"
								. "<div class=\"activity-footer\">{$item["date"]}</div>"
							. "</div>";
					}
				}
			?>
		</div>
		<?php
			$current_mode = "activity";
			require_once("user_navigation.php");

			require("../footer.php");
			require("../header.php");
		?>
	</body>
</html>
<?php
	require_once("../rewriter.php");
	echo rewrite();
	ob_end_flush();
?>
<?php
	function getCandidature($id, &$error_message, &$error_description)
	{
		global $db_connection, $db_table_candidatures;
		$db_query = "SELECT *, HEX(libid), HEX(userid), HEX(`closed-by`) FROM $db_table_candidatures WHERE id = '$id'";
		$db_result = mysql_query($db_query, $db_connection);
		if (!$db_result)
		{
			$error_message = "Failed to retrieve activity: MySQL error";
			$error_description = "Candidature $id could not be read. MySQL error was: '" . mysql_error() . "'";
			return FALSE;
		}
		return mysql_fetch_assoc($db_result);
	}
?>