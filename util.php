<?php
function get_max_id($db_connection)
{
	$db_query = "SELECT id from $db_table_main ORDER BY id DESC LIMIT 1";
	$db_result = mysql_query($db_query, $db_connection)
	or die ("Could not query ID");
	while ($db_entry = mysql_fetch_assoc($db_result))
	{
		return (int)$db_entry["id"];
	}
	return 0;
}

function find_free_file($dir = "", $ext = "")
{
	do
	{
		$file = rand().$ext;
	} while(file_exists($dir . $file));
	return $dir . $file;
}

function find_free_directory($parent = "")
{
	do
	{
		$dir = rand();
	} while(is_dir($parent . $dir));
	return $parent . $dir . DIRECTORY_SEPARATOR;
}

function ensure_upload_dir()
{
	$dir = upload_dir_path();
	if (!is_dir($dir))
	{
		mkdir($dir);
	}
}

function upload_dir_path()
{
	return rtrim(dirname(__FILE__), '/\\').DIRECTORY_SEPARATOR."uploads".DIRECTORY_SEPARATOR;
}

function read_package($package, $include_data = NULL)
{
	static $all_data = NULL;
	if ($all_data == NULL)
	{
		$all_data = array("unique-id", "name", "version", "type", "description", "authors", "tags");
	}

	if ($include_data == NULL)
	{
		$include_data = $all_data;
	}

	$output = array();

	$archive = new ZipArchive();
	if ($archive->open($package) != TRUE)
	{
		$archive->close();
		die ("Package file could not be opened!");
	}

	$doc = new DOMDocument();
	$doc->loadXML($archive->getFromName("definition.ald"));

	if (!$doc->schemaValidate("schema.xsd"))
	{
		die ("ERROR: package definition is not valid!");
	}

	$xp = new DOMXPath($doc);
	$xp->registerNamespace("ald", "ald://package/schema/2012");

	# check if all mentioned files are present
	if (!package_check_for_files($archive, $xp->query("/*/ald:files/ald:doc/@ald:path"), $error_file)
		|| !package_check_for_files($archive, $xp->query("/*/ald:files/ald:src/@ald:path"), $error_file)
		|| !package_check_for_files($archive, $xp->query("@ald:logo-image"), $error_file))
	{
		$archive->close();
		die ("Package references missing file: '" . $error_file . "'!");
	}

	if (in_array('unique-id', $include_data))
	{
		$output['name'] = $xp->query("@ald:unique-id")->item(0)->nodeValue;
	}
	if (in_array('name', $include_data))
	{
		$output['name'] = $xp->query("@ald:name")->item(0)->nodeValue;
	}
	if (in_array('version', $include_data))
	{
		$output['version'] = $xp->query("@ald:version")->item(0)->nodeValue;
	}
	if (in_array('type', $include_data))
	{
		$output['type'] = $xp->query("@ald:type")->item(0)->nodeValue;
	}
	if (in_array('description', $include_data))
	{
		$output['description'] = $xp->query("ald:description")->item(0)->nodeValue;
	}
	if (in_array('authors', $include_data))
	{
		$output['authors'] = array();
		foreach ($xp->query("/*/ald:authors/ald:author") AS $author_node)
		{
			$author = array();

			$author['name'] = get_first_attribute($xp, $author_node, "@ald:name");
			$temp = get_first_attribute($xp, $author_node, "@ald:user-name") AND $author['user-name'] = $temp;
			$temp = get_first_attribute($xp, $author_node, "@ald:homepage") AND $author['homepage'] = $temp;
			$temp = get_first_attribute($xp, $author_node, "@ald:email") AND $author['email'] = $temp;

			$output['authors'][] = $author;
		}
	}
	if (in_array('tags', $include_data))
	{
		$output['tags'] = array();
		foreach ($xp->query("/*/ald:tags/ald:tag") AS $tag_node)
		{
			$output['tags'][] = array('name' => get_first_attribute($xp, $tag_node, "@ald:name"));
		}
	}
	# ...

	$archive->close();
	return $output;
}

function get_first_attribute($xp, $elem, $attr)
{
	foreach ($xp->query($attr, $elem) AS $node)
	{
		return $node->nodeValue;
	}
	return NULL;
}

function package_check_for_files($archive, $file_list, &$error_file = NULL)
{
	foreach ($file_list AS $file_entry)
	{
		if (!$archive->locateName($file_entry->nodeValue))
		{
			$error_file = $file_entry->nodeValue;
			return false;
		}
	}
	return true;
}

# SOURCE: http://www.php.net/manual/de/function.rmdir.php#108113
# recursively remove a directory
function rrmdir($dir) {
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            rrmdir($file);
        else
            unlink($file);
    }
    rmdir($dir);
}


?>