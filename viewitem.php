<!DOCTYPE html>
<html>
	<?php
		require("db.php");
		$page_title = "hi!";
		if (isset($_GET["id"]))
		{
			$id = $_GET["id"];
		}
		else
		{
			header("Location: index.php");
		}
		
		$db_connection = db_ensureConnection();
		$db_query = "SELECT name, author, version, user, description, uploaded, tags FROM $db_table_main WHERE id = '$id' LIMIT 1";
		$db_result = mysql_query($db_query, $db_connection)
		or die ("Failed to read data from database!");

		while ($db_entry = mysql_fetch_object($db_result))
		{
			$item = $db_entry;
			$page_title = "\"$db_entry->name\" (v$db_entry->version)";
		}
		if (!isset($item))
		{
			die ("Could not find this item!");
		}
	?>
	<head>
		<link rel="stylesheet" href="default.css"/>
		<title><?php echo $page_title; ?></title>
	</head>
	<body>
		<?php require("header.php"); ?>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<table>
				<tr>
					<td>Uploaded by:</td>
					<td><a href="viewuser.php?user=<?php echo $item->user; ?>"><?php echo $item->user; ?></a></td>
				</tr>
				<tr>
					<td>Author:</td>
					<td><?php echo $item->author; ?></td>
				</tr>
				<tr>
					<td>Uploaded:</td>
					<td><?php echo $item->uploaded; ?></td>
				</tr>
				<tr>
					<td>Tags:</td>
					<td>
						<?php
							foreach (explode(";", $item->tags) AS $tag)
							{
								echo "<a href='index.php?tag=$tag'>$tag</a> ";
							}
						?>
					</td>
				</tr>
			</table>
			<h3>Description</h3>
			<div>
				<?php echo $item->description; ?>
			</div>
			<?php
				$db_query = "SELECT id, version FROM $db_table_main WHERE name = '$item->name' AND version != '$item->version' ORDER BY version";
				$db_result = mysql_query($db_query, $db_connection)
				or die("ERROR: Could not query for older versions.\n" . mysql_error());

				if (mysql_num_rows($db_result) > 0)
				{
					echo "<h3>Previous versions:</h3><ul>";
					while ($db_entry = mysql_fetch_object($db_result))
					{
						echo "<li><a href='?id=$db_entry->id'>version $db_entry->version</a></li>";
					}
					echo "</ul>";
				}
			?>
		</div>
		<?php require("footer.php"); ?>
	</body>
</html>