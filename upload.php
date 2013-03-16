<?php
	session_start();

	require_once("config/constants.php");
	require_once("util/secure_redirect.php");
	secure_redirect();

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
	if (!$logged_in) {
		require_once("util/ALD.php");
		$api = new ALD(API_URL);
		$user_list = $api->getUserList();
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require("partials/html.head.php"); ?>
		<link rel="stylesheet" type="text/css" href="style/upload.css"/>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
		<script type="text/javascript" src="javascript/modernizr.js"></script>
		<script type="text/javascript" src="javascript/polyfills/loadFormPolyfills.js"></script>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($mode == "start")
				{
			?>
					<form name="up" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post" enctype="multipart/form-data">
						<span class="advice">To upload your code, please fill in the following fields:</span>
						<fieldset>
							<legend>Package information</legend>
							<input type="hidden" name="MAX_FILE_SIZE" value="78643200"/>
							<label for="input_pack_file">File:</label>
							<input id="input_pack_file" type="file" name="package" required="required"/>
						</fieldset>
			<?php
				if (!$logged_in) { ?>
						<fieldset>
							<legend>User login</legend>
							<label for="user-name">User name:</label>
							<input id="user-name" type="text" name="user" list="registered-users" required="required" placeholder="enter your libba.net user name..."/>
							<datalist id="registered-users">
							<?php
								foreach ($user_list AS $user) {
									echo "<option value='$user[name]'></option>";
								}
							?>
							</datalist>
							<label for="input_user_pw">Password:</label>
							<input id="input_user_pw" type="password" name="password" required="required" placeholder="enter your libba.net password... (will be hidden)"/>
						</fieldset>
					<?php } ?>
						<input type="submit" name="submit_btn" value="Upload"/>
						<input type="reset" name="reset_btn" value="Reset"/>
					</form>
			<?php
				}
				else if ($mode == "process")
				{
					if (isset($_FILES["package"]) && ((isset($_POST["user"]) && isset($_POST["password"])) || isset($_SESSION["user"])))
					{
						$user = isset($_POST["user"]) ? $_POST["user"] : $_SESSION["user"];
						$password = isset($_POST["password"]) ? $_POST["password"] : $_SESSION["password"];

						require_once("util/ALD.php");
						try
						{
							$conn = new ALD( SECURE_API_URL );
							$conn->uploadItem($_FILES["package"]["tmp_name"], $user, $password);
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
			<?php
						}
					}
					else
						echo "Failed to upload: required data is missing.";
				}
			?>
		</div>
		<?php require("partials/footer.php"); require("partials/header.php"); ?>
	</body>
</html>