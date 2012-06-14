<!DOCTYPE html>
<html>
	<?php
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
	?>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<link rel="stylesheet" href="upload.css"/>
		<title><?php echo $page_title; ?></title>
		<?php
			if ($mode == "start")
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
								<td>Package:</td>
								<td><input type="hidden" name="MAX_FILE_SIZE" value="78643200"/><input type="file" name="package" onchange="validateData()"/>
							</tr>
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
							<tr>
								<td colspan="2"><input type="submit" name="submit_btn" disabled="disabled" value="Submit!"/></td>
							</tr>
						</table>
					</form>
			<?php
				}
				else if ($mode == "process")
				{
					if (isset($_FILES["package"]) && isset($_POST["user"]) && isset($_POST["password"]))
					{
						require_once("ALD.php");

						try
						{
							$conn = new ALD("http://{$_SERVER["SERVER_NAME"]}/api");
							$id = $conn->uploadItem($_FILES["package"]["tmp_name"], $_POST["user"], $_POST["password"]);
						}
						catch (HttpException $e)
						{
							die ("Failed to upload: {$e->getCode()}<p>{$e->getMessage()}</p>");
						}
			?>
						<b>Successfully uploaded!</b><br/>
						<a href="index.php">Go to index</a><br />
						<a href="viewitem.php?id=<?php echo $id; ?>">View uploaded app or library</a>
			<?php
					}
					else
						die ("Failed to upload: required data is missing.");
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>