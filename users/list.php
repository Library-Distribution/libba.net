<?php
	ob_start();
	session_start();

	require_once('../sortArray.php');
	require_once('../ALD.php');
	require_once('../config/constants.php');
	#require_once('../privilege.php');

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION['user']);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		$page_title = 'View users';

		$page_index = !empty($_GET['page']) ? (int)$_GET['page'] : 0;
		$page_itemcount = !empty($_GET['items']) ? (int)$_GET['items'] : 15;
		$start_index = $page_index * $page_itemcount;

		try
		{
			$users = $api->getUserList($start_index, $page_itemcount + 1);
		}
		catch (HttpException $e)
		{
			$error_message = 'Failed to get user list: API error';
			$error_description = "The list of users could not be retrieved. API error was: '{$e->getMessage()}'";
			break;
		}
		if (count($users) > 0)
		{
			$users = sortArray($users, 'name');
		}
		$error = false;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php require('../templates/html.head.php'); ?>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<?php
				if ($error)
				{
					require('../error.php');
				}
				else # output a list of users
				{
					echo '<ul>';
					$i = 0;
					foreach ($users AS $user)
					{
						$i++;
						if ($i > $page_itemcount)
						{
							break;
						}
						echo "<li><a href='./$user[name]/profile'>$user[name]</a></li>";
					}
					echo '</ul>';

					if (count($users) == 0)
					{
						echo 'No users found';
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
		?>
		</div>
		<?php require('../footer.php'); require('../header.php'); ?>
	</body>
</html>
<?php
	require_once('../rewriter.php');
	echo rewrite();
	ob_end_flush();
?>