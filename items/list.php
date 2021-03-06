<?php
	ob_start();
	session_start();

	require_once("../util/ALD.php");
	require_once("../config/constants.php");
	require_once("../util/sortArray.php");


	require_once('../partials/Notice.php');

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION["user"]);
	$error = true;

	for ($i = 0; $i < 1; $i++)
	{
		$page_title = "Browse ";

		if ($type = (!empty($_GET["type"]) && in_array(strtolower($_GET["type"]), array("app", "lib"))) ? strtolower($_GET["type"]) : NULL)
		{
			$page_title .= ($type == "app") ? "applications" : ($type == "lib" ? "libraries" : "libraries and applications");
		}
		else # probably remove unknown type and reload?
		{
			$page_title .= "libraries and applications";
		}

		$user = !empty($_GET["user"]) ? $_GET["user"] : NULL
			AND $page_title .= " by $user";
		$stdlib = !empty($_GET["stdlib"]) ? $_GET["stdlib"] : "both"
			AND $page_title .= !empty($_GET["stdlib"]) ? " (lib standard)" : "";
		$unreviewed = !empty($_GET['reviewed']) ? $_GET['reviewed'] : 'yes';
		$tags = isset($_GET["tags"]) ? explode("|", $_GET["tags"]) : NULL
			AND $page_title .= " (tags: " . implode($tags, ", ") . ")";

		$page_index = !empty($_GET["page"]) ? (int)$_GET["page"] : 0;
		$page_itemcount = !empty($_GET["items"]) ? (int)$_GET["items"] : 20;
		$start_index = $page_index * $page_itemcount;

		try
		{
			$items = $api->getItemList($start_index, $page_itemcount + 1, $type, $user, NULL, $tags, "latest", $stdlib, $unreviewed);
		}
		catch (HttpException $e)
		{
			$error_message = "Failed to get item list: API error";
			$error_description = "The requested list of items could not be retrieved. API error was: '{$e->getMessage()}'";
			break;
		}
		if (count($items) > 0)
		{
			$items = sortArray($items, "name");
			foreach ($items AS &$item) {
				try {
					$item_data = $api->getItemById($item['id']);
				} catch (HttpException $e) {
					$error_message = 'Failed to get item details: API error';
					$error_description = 'The details on item "' . $item['name'] . '" could not be read. API error was: "' . $e->getMessage() . '"';
					break;
				}
				$item = array_merge($item, $item_data);
			}
		}
		$error = false;
	}
?>
<!DOCTYPE html>
<html class="no-js">
	<head>
		<?php require("../partials/html.head.php"); ?>

		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
		<script type="text/javascript" src="javascript/default.js"></script>

		<link rel="stylesheet" type="text/css" href="style/items/list.css"/>
	</head>
	<body>
		<h1 id="page-title"><?php echo $page_title; ?></h1>
		<div id="page-content">
			<div id="items-list" class="js-ui-accordion">
			<?php
				if ($error)
				{
					error($error_message, $error_description, true);
				}
				else
				{
					$last_letter = "";
					$i = 0;
					foreach ($items as $item)
					{
						$i++;
						if ($i > $page_itemcount)
						{
							break;
						}

						$current_letter = strtoupper(substr($item['name'], 0, 1));
						if (!ctype_alpha($current_letter))
						{
							$current_letter = ".#?1";
						}
						if ($current_letter != $last_letter)
						{
							if ($last_letter != "")
							{
								echo "</ul></div></div>";
							}
							echo "<div class='letter-container' id='items$current_letter'><h3 class='js-ui-accordion-header'>$current_letter</h3><div id='items_$current_letter'><ul>";
						}
						echo "<li id='item{$item['id']}' class='$item[type]'>",
								"<a class='item' href='./{$item['id']}'>{$item['name']}</a> (v{$item['version']}) by <a class='userlink' href='users/{$item['user']['name']}/profile'>{$item['user']['name']}</a>",
								$item['reviewed'] ? '' : '<a title="This item has not yet been reviewed" href="reviews/' . $item['id'] . '" class="unreviewed"></a>',
							"</li>";
						$last_letter = $current_letter;
					}
					if (count($items) > 0)
					{
						echo "</ul></div>";
					}
					else
					{
						echo "<b>No items found that match your query.</b>";
					}

					if ($page_index > 0)
					{
						echo "<a class='next-previous' id='prev' href='?items=$page_itemcount&amp;page=".($page_index - 1)."'>Previous page</a>";
					}

					if (count($items) > $page_itemcount)
					{
						echo "<a class='next-previous' id='next' href='?items=$page_itemcount&amp;page=".($page_index + 1)."'>Next page</a>";
					}
				}
			?>
			</div>
		</div>
		<?php require("../partials/header.php"); require("../partials/footer.php"); ?>
	</body>
</html>
<?php
	require_once("../util/rewriter.php");
	echo rewrite();
	ob_end_flush();
?>