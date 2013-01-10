<?php
	require_once('ALD.php');
	require_once('config/constants.php');

	header('Content-type: application/rss+xml');

	$items = array(
				array('title' => 'libba.net code listing',
					'link' => 'http://libba.net/items/',
					'description' => 'Displays a list of all uploaded code'),
				array('title' => 'libba.net users',
					'link' => 'http://libba.net/users/',
					'description' => 'The list of registered libba.net users'),
				array('title' => 'libba.net help',
					'link' => 'http://libba.net/help',
					'description' => 'help / FAQ for libba.net'),
				array('title' => 'libba.net code upload',
					'link' => 'http://libba.net/upload',
					'description' => 'Here you can upload new code'),
				array('title' => 'about libba.net',
					'link' => 'http://libba.net/about',
					'description' => 'libba.net about page')
			);

	$api = new ALD( API_URL );

	foreach ($api->getItemList() AS $item)
	{
		$item_data = $api->getItemById($item['id']);
		$items[] = array('title' => "$item[name] v$item[version]",
					'link' => "http://libba.net/items/$item[id]",
					'description' => htmlentities($item_data['description'], 0, 'UTF-8'),
					'pubDate' => $item_data['uploaded']);
	}

	foreach ($api->getUserList() AS $user)
	{
		$url_name = urlencode($user['name']);
		$items[] = array('title' => "profile for $user[name]",
					'link' => "http://libba.net/users/$url_name/profile",
					'description' => "the libba.net profile page for $user[name]");
		$items[] = array('title' => "activity for $user[name]",
					'link' => "http://libba.net/users/$url_name/activity",
					'description' => "recent activity by $user[name] on libba.net");
		$items[] = array('title' => "items by $user[name]",
					'link' => "http://libba.net/users/$url_name/items",
					'description' => "libraries and apps uploaded by $user[name] to libba.net");
		$items[] = array('title' => "achievements by $user[name]",
					'link' => "http://libba.net/users/$url_name/achievements",
					'description' => "achievements made by $user[name] on libba.net");
	}

	# todo: fetch more items, such as reviews and candidates

	echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<rss version="2.0">
	<channel>
		<title>libba.net</title>
		<link>http://libba.net</link>
		<description>The AutoHotkey code managing system host</description>
		<image>
			<url>http://libba.net/images/logo.png</url>
			<title>libba.net</title>
			<link>http://libba.net</link>
		</image>
		<language>en-us</language>
		<?php
			foreach ($items AS $item)
			{
				echo '<item>';
				foreach ($item AS $tag => $value)
				{
					echo "<$tag>$value</$tag>";
				}
				echo '</item>';
			}
		?>
	</channel>
</rss>