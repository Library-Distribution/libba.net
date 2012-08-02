<?php
	session_start();
	if (empty($_SERVER["HTTPS"]) && $_SERVER["SERVER_ADDR"] != "127.0.0.1")
	{
		header("Location: https://ahk4.net/user/maulesel/upload");
	}
	if ($_POST && $_FILES)
	{
		$page_title = "Uploading...";
		$mode = "process";
	}
	else
	{
		$page_title = "Upload a new library or application";
		$mode = "start";
	}
	$logged_in = isset($_SESSION["user"]);
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("templates/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/upload.css"/>
		<?php
			if ($mode == "start" && !$logged_in)
			{
		?>
				<script type="text/javascript">
					function validateData()
					{
						if (document.up.package.value != ""
							&& document.up.user.value != ""
							&& document.up.password.value != "")
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
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($mode == "start")
				{
			?>
					Fill in the following fields:
					<form name="up" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post" enctype="multipart/form-data">
						<table width="100%">
							<col width="25%"/>
							<col width="75%"/>
							<tr class="form-header">
								<td colspan="2">Your application or library:</td>
							</tr>
							<tr>
								<td>Package:</td>
								<td><input type="hidden" name="MAX_FILE_SIZE" value="78643200"/><input type="file" name="package" onchange="validateData()"/>
							</tr>
			<?php
					if (!$logged_in) { ?>
							<tr class="form-header">
								<td colspan="2">You:</td>
							</tr>
							<tr>
								<td>User name:</td>
								<td><input type="text" name="user" onchange="validateData()"/></td>
							</tr>
							<tr>
								<td>Password:</td>
								<td><input type="password" name="password" onchange="validateData()"/></td>
							</tr>
					<?php } ?>
							<tr>
								<td colspan="2"><input type="submit" name="submit_btn" <?php echo !$logged_in ? "disabled=\"disabled\"" : "" ?> value="Submit!"/></td>
							</tr>
						</table>
					</form>
			<?php
				}
				else if ($mode == "process")
				{
					if (isset($_FILES["package"]) && ((isset($_POST["user"]) && isset($_POST["password"])) || isset($_SESSION["user"])))
					{
						$user = isset($_POST["user"]) ? $_POST["user"] : $_SESSION["user"];
						$password = isset($_POST["password"]) ? $_POST["password"] : $_SESSION["password"];

						require_once("ALD.php");
						require_once("get_API_URL.php");
						try
						{
							$conn = new ALD(get_API_URL(true));
							$id = $conn->uploadItem($_FILES["package"]["tmp_name"], $user, $password);
						}
						catch (HttpException $e)
						{
							echo "Failed to upload: {$e->getCode()}<p>{$e->getMessage()}</p>";
							$error = true;
						}
						if (!isset($error))
						{
			?>
							<b>Successfully uploaded!</b><br/>
							<a href="index">Go to index</a><br />
							<a href="items/<?php echo $id; ?>">View uploaded app or library</a>
			<?php
						}
					}
					else
						echo "Failed to upload: required data is missing.";
				}
			?>
		</div>
		<?php require("footer.php"); require("header.php"); ?>
	</body>
</html>