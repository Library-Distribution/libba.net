<?php
	session_start();

	require_once("sortArray.php");
	require_once("ALD.php");
	require_once("get_API_URL.php");
	require_once("user_input.php");
	require_once("privilege.php");

	$api = new ALD(get_API_URL(true));
	$logged_in = isset($_SESSION["user"]);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		if (isset($_GET["user"]))
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
			$possible_modes = array("profile" => "Profile",
									"activity" => "Activity",
									"items" => "Libs &amp; Apps",
									"achievements" => "Achievements",
									"modify" => "Change settings",
									"suspend" => "Suspend user");

			$mode = "profile";
			if (isset($_GET["mode"]))
			{
				if (in_array(strtolower($_GET["mode"]), array_keys($possible_modes)))
				{
					$mode = strtolower($_GET["mode"]);
				}
				else # unknown mode - ignore
				{
					header("Location: ?user=$user");
					exit;
				}
			}
			if (($mode == "modify" && (!$logged_in || $_SESSION["user"] != $user))
				|| ($mode == "suspend" && (!$logged_in || !hasPrivilege($_SESSION["user"], PRIVILEGE_USER_MANAGE) || $user == $_SESSION["user"])))
			{
				header("Location: ?user=$user");
				exit;
			}

			if ($mode == "profile" || $mode == "activity" || $mode == "modify")
			{
				require_once("api/db.php");
				require_once("db2.php");

				$db_connection = db_ensure_connection();
			}

			if ($mode == "profile" || $mode == "modify")
			{
				$db_query = "SELECT * FROM $db_table_user_profile WHERE id = UNHEX('{$user_data["id"]}')";
				$db_result = mysql_query($db_query, $db_connection);
				if (!$db_result)
				{
					$error_message = "Failed to retrieve profile: MySQL error";
					$error_description = "Could not read profile settings. MySQL error was: '" . mysql_error() . "'";
					break;
				}
				$user_profile = mysql_fetch_assoc($db_result);

				if ($mode == "modify")
				{
					if (!db_get_enum_column_values($db_table_user_profile, "allow_mails", $contact_options))
					{
						$error_message = "Cannot modify profile: MySQL error";
						$error_description = "The possible options for 'allow_mails' could not be retrieved. MySQL error was: '" . mysql_error() . "'";
						break;
					}
					if (!db_get_enum_column_values($db_table_user_profile, "show_mail", $mail_options))
					{
						$error_message = "Cannot modify profile: MySQL error";
						$error_description = "The possible options for 'show_mail' could not be retrieved. MySQL error was: '" . mysql_error() . "'";
						break;
					}
					if (!db_get_enum_column_values($db_table_user_profile, "site_theme", $theme_options))
					{
						$error_message = "Cannot modify profile: MySQL error";
						$error_description = "The possible options for 'site_theme' could not be retrieved. MySQL error was: '" . mysql_error() . "'";
						break;
					}
				}
			}

			if ($mode == "modify" && !empty($_POST))
			{
				# todo: verify password
				# require user to enter his password once again

				if (!empty($_POST["username"]) && $_POST["username"] != $user)
				{
					try {
						$api->modifyUser($user, $_SESSION["password"], $_POST["username"]);
					} catch (HttpException $e) {
						$error_message = "Failed to update user profile: API error";
						$error_description = "New user name could not be saved. API error was: '{$e->getMessage()}'";
						break;
					}
					$redirect_user = $_POST["username"];
					$_SESSION["user"] = $_POST["username"];
				}
				if (!empty($_POST["mail"]) && $_POST["mail"] != $user_profile["mail"])
				{
					# todo: deactivate account, send activation mail

					$mail = mysql_real_escape_string($_POST["mail"]);
					$db_query = "UPDATE $db_table_user_profile Set mail = '$mail' WHERE id = UNHEX('{$_SESSION["userID"]}')";

					$db_result = mysql_query($db_query, $db_connection);
					if (!$db_result || mysql_affected_rows() != 1)
					{
						$error_message = "Failed to update user profile: MySQL error";
						$error_description = "New email could not be saved. MySQL error was: '" . mysql_error() . "'";
						break;
					}
				}
				if (!empty($_POST["site_theme"]) && $_POST["site_theme"] != $user_profile["site_theme"] && in_array($_POST["site_theme"], $theme_options))
				{
					$theme = mysql_real_escape_string($_POST["site_theme"]);
					$db_query = "UPDATE $db_table_user_profile Set site_theme = '$theme' WHERE id = UNHEX('{$_SESSION["userID"]}')";

					$db_result = mysql_query($db_query, $db_connection);
					if (!$db_result || mysql_affected_rows() != 1)
					{
						$error_message = "Failed to update user profile: MySQL error";
						$error_description = "New website theme could not be saved. MySQL error was: '" . mysql_error() . "'";
						break;
					}
				}
				if (!empty($_POST["show_mail"]) && $_POST["show_mail"] != $user_profile["show_mail"] && in_array($_POST["show_mail"], $mail_options))
				{	
					$show_mail = mysql_real_escape_string($_POST["show_mail"]);
					$db_query = "UPDATE $db_table_user_profile Set show_mail = '$show_mail' WHERE id = UNHEX('{$_SESSION["userID"]}')";

					$db_result = mysql_query($db_query, $db_connection);
					if (!$db_result || mysql_affected_rows() != 1)
					{
						$error_message = "Failed to update user profile: MySQL error";
						$error_description = "New setting for email visibility could not be saved. MySQL error was: '" . mysql_error() . "'";
						break;
					}
				}
				if (!empty($_POST["allow_mails"]) && $_POST["allow_mails"] != $user_profile["allow_mails"] && in_array($contact_options, $_POST["allow_mails"]))
				{
					$allow_mails = mysql_real_escape_string($_POST["allow_mails"]);
					$db_query = "UPDATE $db_table_user_profile Set allow_mails = '$allow_mails' WHERE id = UNHEX('{$_SESSION["userID"]}')";

					$db_result = mysql_query($db_query, $db_connection);
					if (!$db_result || mysql_affected_rows() != 1)
					{
						$error_message = "Failed to update user profile: MySQL error";
						$error_description = "New setting for allowing contacting could not be saved. $allow_mails MySQL error was: '" . mysql_affected_rows() . mysql_error() . "'";
						break;
					}
				}
				# todo: support changing password
				if (isset($redirect_user))
				{
					header("Location: ?user=$redirect_user");
				}
			}
			else if ($mode == "items")
			{
				try
				{
					$libs = $api->getItemList(0, "all", "lib", $user, NULL, NULL, "latest");
					$apps = $api->getItemList(0, "all", "app", $user, NULL, NULL, "latest");
				}
				catch (HttpException $e)
				{
					$error_message = "Failed to retrieve uploaded items: API error";
					$error_description = "Uploaded items could not be retrieved. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
					break;
				}
				$libs = sortArray($libs, array("name" => false, "version" => true));
				$apps = sortArray($apps, array("name" => false, "version" => true));
			}
			else if ($mode == "achievements")
			{
				$achievements = array();

				try
				{
					$libs = $api->getItemList(0, "all", "lib", $user, NULL, NULL, NULL, "yes");
				}
				catch (HttpException $e)
				{
					$error_message = "Failed to retrieve acheivements: API error";
					$error_description = "Libraries in stdlb coud not be retrieved. API error was: '{$e->getMessage()}' (code: {$e->getCode()})";
					break;
				}
				$libs = sortArray($libs, array("name" => false, "version" => true));

				foreach ($libs as $lib)
				{
					$achievements[] = array("text" => "$user's library {$lib["name"]} v{$lib["version"]} is part of the standard lib for AutoHotkey",
											"image" => "images/achievements/stdlib.png",
											"link" => "items?id=" . $lib["id"]);
				}
			}
			else if ($mode == "activity")
			{
				$body_char_count = 255; # config

				$activity_item_count = !empty($_GET["items"]) ? strtolower($_GET["items"]) : 15;
				$db_limit = $activity_item_count == "all" ? "" : " LIMIT $activity_item_count";

				$activity = array();
				$retrieved_items = array();

				# joined
				$activity[] = array("header" => "$user joined ALD",
									"text" => "Welcome to ALD, $user! If you have any questions, consult the <a href=\"help\">help</a> or contact us!",
									"image" => "images/activity/joined.png",
									"date" => $user_data["joined"],
									"link" => "user=$user");

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
					$activity[] = array("header" => "$user uploaded <a href=\"items?id=$id\">{$item["name"]} (v{$item["version"]})</a>",
										"text" => $retrieved_items[$id]["description"],
										"image" => "images/activity/upload.png",
										"date" => $retrieved_items[$id]["uploaded"],
										"link" => "items?id=$id");
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
					$activity[] = array("header" => "$user commented on <a href=\"review?id=$id\">Code Review for {$item["name"]} v{$item["version"]}</a>",
										"text" => $comment["comment"],
										"image" => "images/activity/review-comment.png",
										"date" => $comment["date"],
										"link" => "review?id=$id"); # todo: anchor for comment
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
					$activity[] = array("header" => "$user proposed <a href=\"items?id=$id\">{$item["name"]} (v{$item["version"]})</a> for the stdlib",
										"text" => $candidature["text"],
										"image" => "images/activity/candidature.png",
										"date" => $candidature["date"],
										"link" => "candidatures?id=" . $candidature["id"]);

					if ($candidature["closed"])
					{
						$user = $api->getUserById($candidature["HEX(`closed-by`)"]);
						$accepted = $item["default"] ? "accepted" : "rejected";
						$activity[] = array("header" => "The stdlib candidature for {$item["name"]} v{$item["version"]} has been $accepted by <a href=\"users?user={$user["name"]}\">{$user["name"]}</a>",
											"text" => $candidature["closed-comment"],
											"image" => "images/activity/candidature-$accepted.png",
											"date" => $candidature["closed-date"],
											"link" => "candidatures?id={$candidature["id"]}#closecomment");
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

					$activity[] = array("header" => "$user commented on <a href=\"candidatures?id=$id\">{$item["name"]} v{$item["version"]} - Stdlib candidature</a>",
										"text" => $comment["comment"],
										"image" => "images/activity/candidature-comment.png",
										"date" => $comment["date"],
										"link" => "candidatures?id=$id");
				}

				$activity = sortArray($activity, array("date" => true));
				if ($activity_item_count != "all")
				{
					$activity = array_slice($activity, 0, $activity_item_count);
				}
			}
		}
		else
		{
			$page_title = "View users";

			$page_index = !empty($_GET["page"]) ? (int)$_GET["page"] : 0;
			$page_itemcount = !empty($_GET["items"]) ? (int)$_GET["items"] : 15;
			$start_index = $page_index * $page_itemcount;

			try
			{
				$users = $api->getUserList($start_index, $page_itemcount + 1);
			}
			catch (HttpException $e)
			{
				$error_message = "Failed to get user list: API error";
				$error_description = "The list of users could not be retrieved. API error was: '{$e->getMessage()}'";
				break;
			}
			$users = sortArray($users, "name");
		}
		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php
			require("templates/html.head.php");
			if (isset($user))
			{
				# ATTENTION:
				# # # # # # # # # # # # # # # # # # # # # # # # # # # #
				# when changing this to always include viewuser.css,  #
				# remember the fact that it influences #page-content. #
				# # # # # # # # # # # # # # # # # # # # # # # # # # # #
		?>
			<link rel="stylesheet" type="text/css" href="viewuser.css"/>
			<link rel="stylesheet" type="text/css" href="viewuser.<?php echo $mode; ?>.css"/>
		<?php }  ?>
	</head>
	<body>
		<h1 id="page-title"><?php
				if (isset($user))
				{
					echo "<img alt=\"$user's avatar\" id=\"user-gravatar\" src=\"http://gravatar.com/avatar/{$user_data['mail']}?s=50&amp;d=mm\"/>";
				}
				echo $page_title;
			?></h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					require("error.php");
				}
				else if (!isset($user)) # output a list of users
				{
					echo "<ul>";
					$i = 0;
					foreach ($users AS $user)
					{
						$i++;
						if ($i > $page_itemcount)
						{
							break;
						}
						echo "<li><a href='?user={$user['name']}'>{$user['name']}</a></li>";
					}
					echo "</ul>";

					if (count($users) == 0)
					{
						echo "No users found";
					}

					if ($page_index > 0)
					{
						echo "<a class='next-previous' id='prev' href='?items=$page_itemcount&amp;page=".($page_index - 1)."'>Previous page</a>";
					}

					# check if there are more users
					if (count($users) > $page_itemcount)
					{
						echo "<a class='next-previous' id='next' href='?items=$page_itemcount&amp;page=".($page_index + 1)."'>Next page</a>";
					}
				}
				else if ($mode == "activity")
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
				else if ($mode == "profile") # output a user profile
				{
				?>
					<table>
							<tr>
								<td>email:</td>
								<td>
				<?php
					if ($user_profile["show_mail"] == "public" || ($user_profile["show_mail"] == "members" && $logged_in))
					{
						echo "<img id=\"user-mail\" alt=\"$user's mail address\" src=\"mailimage.php?user={$user_data["id"]}\"/>";
					}
					if ($user_profile["allow_mails"])
					{
						echo "<a href=\"#\">Contact $user</a>";
					}
				?>
								</td>
							</tr>
							<tr>
								<td>member since:</td>
								<td><?php echo $user_data["joined"]; ?></td>
							</tr>
						</table>
				<?php
				}
				else if ($mode == "items") # output apps and lib uploaded
				{
					foreach (array("Libraries" => $libs, "Applications" => $apps) AS $set_name => $set)
					{
						if ($item_count = count($set))
						{
							echo "<h2>$set_name uploaded ($item_count)</h2>";
							foreach ($set AS $item)
							{
								echo "<a href='items?id={$item['id']}' class=\"user-item\">{$item['name']} (v{$item['version']})</a>";
							}
						}
					}
				}
				else if ($mode == "achievements")
				{
					foreach ($achievements AS $a)
					{
						echo "<div class=\"achievement\"><a href=\"{$a["link"]}\"><img class=\"achievement-icon\" src=\"{$a["image"]}\"/></a>{$a["text"]}</div>";
					}
				}
				else if ($mode == "modify" && empty($_POST))
				{
			?>
					<form action="#" method="post">
						<table style="display: inline-table">
							<tr>
								<td><label for="username">user name:</label></td>
								<td><input type="text" name="username" value="<?php echo $user; ?>"/></td>
							</tr>
							<tr>
								<td><label for="mail">email:</label></td>
								<td><input type="text" name="mail" value="<?php echo $user_profile["mail"]; ?>"/></td>
							</tr>
							<tr>
								<td><label for="site_theme">website theme:</label></td>
								<td>
									<select name="site_theme">
						<?php
										foreach ($theme_options AS $theme)
										{
											echo "<option value=\"$theme\" "
														. ($user_profile["site_theme"] == $theme ? "selected=\"selected\"" : "")
														. ">$theme</option>";
										}
						?>
									</select>
								</td>
							</tr>
							<tr>
								<td><label for="show_mail">email visibility:</label></td>
								<td>
									<select name="show_mail">
						<?php
										foreach ($mail_options AS $option)
										{
											echo "<option value=\"$option\" "
														. ($user_profile["show_mail"] == $option ? "selected=\"selected\"" : "")
														. ">$option</option>";
										}
						?>
									</select>
								</td>
							</tr>
							<tr>
								<td><label for="allow_mails">allow users to contact me:</label></td>
								<td>
									<select name="allow_mails">
						<?php
										foreach ($contact_options AS $value)
										{
											echo "<option value=\"$value\" "
														. ($user_profile["allow_mails"] == $value ? "selected=\"selected\"" : "")
														. ">$value</option>";
										}
						?>
									</select>
								</td>
							</tr>
							<!-- TODO: support password change (enter twice) -->
							<tr>
								<td colspan="2">
									<input type="submit" value="Submit"/>
								</td>
							</tr>
						</table>
					</form>
			<?php
				}
				else if ($mode == "modify")
				{
					echo "Your profile has been updated.";
				}
			?>
		</div>
		<?php
			if (isset($_GET["user"]))
			{
				echo "<div id=\"user-navigation\">";
				foreach ($possible_modes AS $m => $name)
				{
					$id = ($mode == $m) ? "id=\"nav-current\"" : "";
					$style = ($m == "modify" && (!$logged_in || $_SESSION["user"] != $user)) || ($m == "suspend" && (!$logged_in || !hasPrivilege($_SESSION["privileges"], PRIVILEGE_USER_MANAGE) || $user == $_SESSION["user"]))
							? "style=\"display: none\""
							: "";
					echo "<a href=\"?user=$user&amp;mode=$m\" $id $style><div>$name</div></a>";
				}
				echo "</div>";
			}
			?>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>
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