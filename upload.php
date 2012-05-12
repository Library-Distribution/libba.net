<!DOCTYPE html>
<html>
	<?php
		if (isset($_GET["uploaded"]))
		{
			if ($_GET["uploaded"] == "success")
			{
				$page_title = "Successfully uploaded.";
				$mode = "done";
			}
			else if ($_GET["uploaded"] == "error")
			{
				$page_title = "Error while uploading.";
				$mode = "error";
			}
		}
		else if ($_POST)
		{
			$page_title = "Uploading...";
			$mode = "process";
		}
		else
		{
			$page_title = "Upload a new library or application";
			$mode = "start";
		}
	?>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
		<?php
			if ($mode == "start")
			{
		?>
				<script type="text/javascript">
					function validateData()
					{
						if (document.up.pack_name.value != ""
							&& document.up.pack_file.value != ""
							&& document.up.user_name.value != ""
							&& document.up.user_pw.value != "")
						{
							document.up.submit_btn.disabled = false;
						}
						else
						{
							document.up.submit_btn.disabled = true;
						}
					}
				</script>
		<?php
			}
		?>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($mode == "start")
				{
			?>
					Fill in the following fields:
					<form name="up" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">
						<table width="100%">
							<col width="25%"/>
							<col width="75%"/>
							<tr class="form-header">
								<td colspan="2">Your application or library:</td>
							</tr>
							<tr>
							<!-- todo: read from definition file -->
								<td>Name:</td>
								<td><input type="text" name="pack_name" onchange="validateData()"/></td>
							</tr>
							<!-- todo: read from definition file -->
							<tr>
								<td>Version:</td>
								<td><input type="text" name="pack_version" onchange="validateData()"/></td>
							</tr>
							<!-- todo: read from definition file -->
							<tr>
								<td>Type:</td>
								<td>
									<input type="radio" name="pack_type" onchange="validateData()" value="app">Application</input><br />
									<input type="radio" name="pack_type" onchange="validateData()" value="lib" checked="checked">Library or function</input>
								</td>
							</tr>
							<tr>
								<td>Description:</td>
								<td><textarea name="pack_description"></textarea></td>
							</tr>
							<tr>
								<td>Tags:</td>
								<td><textarea name="pack_tags"></textarea>(separated by ";")</td>
							</tr>
							<tr>
								<td>Package:</td>
								<td><input type="file" name="pack_file" onchange="validateData()"/>
							</tr>
							<tr class="form-header">
								<td colspan="2">You:</td>
							</tr>
							<tr>
								<td>User name:</td>
								<td><input type="text" name="user_name" onchange="validateData()"/></td>
							</tr>
							<tr>
								<td>Password:</td>
								<td><input type="password" name="user_pw" onchange="validateData()"/></td>
							</tr>
							<tr>
								<td colspan="2"><input type="submit" name="submit_btn" disabled="disabled" value="Submit!"/></td>
							</tr>
						</table>
					</form>
			<?php
				}
				else if ($mode == "process")
				{
					require("db.php");
					require("users.php");
					require("util.php");

					# todo: read from definition file
					$pack_name = $_POST["pack_name"];
					$pack_version = $_POST["pack_version"];
					$pack_type = $_POST["pack_type"];
					$pack_description = $_POST["pack_description"];
					$pack_tags = $_POST["pack_tags"];

					# retrieve posted parameters
					$pack_file = $_POST["pack_file"];
					$user_name = $_POST["user_name"];
					$user_pw = $_POST["user_pw"];

					# check if all required data is present
					if (!isset($pack_name) || !isset($pack_type) || !isset($pack_file) || !isset($user_name) || !isset($user_pw) || !isset($pack_version) || !isset($pack_description) || !isset($pack_tags))
					{
						die("Error: you did not fill in all required fields!");
					}
					echo "FILE: $pack_file";

					# connect to database server
					$db_connection = db_ensureConnection();

					# escape data to prevent SQL injection
					$escaped_name = mysql_real_escape_string($pack_name, $db_connection);
					$escaped_type = mysql_real_escape_string($pack_type, $db_connection);
					$escaped_version = mysql_real_escape_string($pack_version, $db_connection);
					$escaped_description = mysql_real_escape_string($pack_description, $db_connection);
					$escaped_tags = mysql_real_escape_string($pack_tags, $db_connection);
					$escaped_user = mysql_real_escape_string($user_name, $db_connection);

					# todo: validate version string / convert to number

					# validate type
					if ($escaped_type != "app" && $escaped_type != "lib")
					{
						echo $escaped_type;
						die ("Invalid type was specified.");
					}

					# validate user & password
					validateLogin($user_name, $user_pw);

					# upload file
					$file = find_free_file("uploads\\", ".7z");
					if ($_FILES["pack_file"]["size"] > 262144000) # 250 MB
					{
						die ("File is too large ( > 250MB ).");
					}
					$file_content = read_definition_file($_FILES["pack_file"]["tmp_name"]);
					# todo: restrictions / validate file / read data from file
					move_uploaded_file($_FILES["pack_file"]["tmp_name"], "uploads\\".$file);

					$datetime = date("Y-m-d H:i:s");

					# check if there's any version of the app
					$db_query = "SELECT user FROM $db_table_main WHERE name = '$escaped_name' LIMIT 1";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("Could not query for older versions!");
					if ($db_result)
					{
						# if so, check if it's the same user as now
						while ($db_entry = mysql_fetch_object($db_result))
						{
							if ($db_entry->user != $escaped_user)
							{
								die ("The user '$user_name' is not allowed to update the library or app '$pack_name'");
							}
						}
					}

					# check if this specific version had already been uploaded or not
					$db_query = "SELECT id FROM $db_table_main WHERE name = '$escaped_name' AND version = '$escaped_version' LIMIT 1";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("Could not query for package version!");
					if ($db_result)
					{
						while ($db_entry = mysql_fetch_object($db_result))
						{
							die ("The specified version '$pack_version' of package '$pack_name' has already been uploaded!");
						}
					}

					# add a database entry
					$db_query = "INSERT INTO $db_table_main (name, type, version, file, user, description, tags, uploaded) VALUES ('$escaped_name', '$escaped_type', '$escaped_version', '$file', '$escaped_user', '$escaped_description', '$escaped_tags', '$datetime')";
					mysql_query($db_query, $db_connection)
					or die ("Could not add a new version to database!".mysql_error());

					# todo:
					$db_query = "SELECT id FROM $db_table_main WHERE name = '$escaped_name' AND version = '$escaped_version' LIMIT 1";
					$db_result = mysql_query($db_query, $db_connection)
					or die ("Could not retrieve ID of uploaded item: " . mysql_error());

					if ($db_result)
					{
						while ($db_entry = mysql_fetch_object($db_result))
						{
							$id = $db_entry->id;
						}
					}
					header("Location: " . $_SERVER['PHP_SELF'] . "?uploaded=success" . ((isset($id)) ? "&id=$id" : ""));
				}
				else if ($mode == "done")
				{
			?>
					<b>Successfully uploaded!</b>
					<a href="index.php">Go to index</a><br />
			<?php
					if (isset($_GET["id"]))
					{
						# todo: possibly emit more data, using the id (if present)
						echo "<a href=\"viewitem.php?id=\"".$_GET["id"]."\">View uploaded app or library</a>";
						sleep(10);
						header("Location: viewitem.php?id=".$_GET["id"]);
					}
					else
					{
						sleep(10);
						header("Location: index.php");
					}
				}
				else if ($mode == "error")
				{
					# todo
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>