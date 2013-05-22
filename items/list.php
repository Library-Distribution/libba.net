<?php
$layout = 'items/list';
require '../util/createLayout.php';
exit;
?>

<?php
function logic() {
	require_once '../util/LibbaException.php';
	require_once '../util/ALD.php';
	require_once '../config/constants.php';
	require_once '../util/sortArray.php';

	$api = new ALD( API_URL );
	$logged_in = isset($_SESSION['user']);

	$page_title = 'Browse ';
	if ($type = (!empty($_GET['type']) && in_array(strtolower($_GET['type']), array('app', 'lib'))) ? strtolower($_GET['type']) : NULL)
	{
		$page_title .= ($type == 'app') ? 'applications' : ($type == 'lib' ? 'libraries' : 'libraries and applications');
	}
	else # probably remove unknown type and reload?
	{
		$page_title .= 'libraries and applications';
	}

	$user = !empty($_GET['user']) ? $_GET['user'] : NULL
		AND $page_title .= ' by ' . $user;
	$stdlib = !empty($_GET['stdlib']) ? $_GET['stdlib'] : 'both'
		AND $page_title .= !empty($_GET['stdlib']) ? ' (lib standard)' : '';
	$unreviewed = !empty($_GET['reviewed']) ? $_GET['reviewed'] : 'yes';
	$tags = isset($_GET['tags']) ? explode('|', $_GET['tags']) : NULL
		AND $page_title .= ' (tags: ' . implode($tags, ', ') . ')';

	$page_index = !empty($_GET['page']) ? (int)$_GET['page'] : 0;
	$page_itemcount = !empty($_GET['items']) ? (int)$_GET['items'] : 20;
	$start_index = $page_index * $page_itemcount;

	try {
		$items = $api->getItemList($start_index, $page_itemcount + 1, $type, $user, NULL, $tags, 'latest', $stdlib, $unreviewed);
	} catch (HttpException $e) {
		throw new LibbaException('Failed to get item list: API error',
				'The requested list of items could not be retrieved. API error was: "' . htmlentities($e->getMessage()) . '"');
	}

	if (count($items) > 0) {
		$items = sortArray($items, 'name');
		$grouped_items = array();

		foreach ($items AS $item) {
			# find more information on the item
			try {
				$item_data = $api->getItemById($item['id']);
			} catch (HttpException $e) {
				throw new LibbaException('Failed to get item details: API error',
						'The details on item "' . $item['name'] . '" could not be read. API error was: "' . htmlentities($e->getMessage()) . '"');
			}
			$item = array_merge($item, $item_data);

			# group the item by first letter of its name
			$letter = strtoupper(substr($item['name'], 0, 1));
			if (!ctype_alpha($letter)) {
				$letter = ".#?1";
			}
			if (!isset($grouped_items[$letter])) {
				$grouped_items[$letter] = array();
			}
			array_push($grouped_items[$letter], $item);
		}
	}

	return array('page_title' => $page_title, 'items' => $grouped_items);
}
?>