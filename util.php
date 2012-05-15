<?php
require_once("archive.php");

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

function read_definition_file($file)
{
	# create a temp directory
	$dir = upload_dir_path() . find_free_directory(upload_dir_path());
	mkdir($dir);

	# extract data
	if (($retVal = archive_extract_file($file, "definition.ald", $dir)) != 0)
	{
		# delete temp dir
		rrmdir($dir);
		die ("Could not extract archive '$file'to '$dir'!\n".$retVal);
	}
	$definition = file_get_contents($dir."definition.ald");

	# delete temp dir
	rrmdir($dir);

	return $definition;
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