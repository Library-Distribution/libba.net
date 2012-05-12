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
	return $file;
}

function find_free_directory($parent = "")
{
	do
	{
		$dir = rand();
	} while(is_dir($parent . $dir));
	return $dir;
}

function read_definition_file($file)
{
	$dir = find_free_directory();
	mkdir($dir);
	if (extract_archive($file, $dir) != 0)
	{
		die ("Could not extract archive!");
	}
	$definition = file_get_contents($dir ."\\definition.ald");
	rmdir($dir);
	return $definiton;
}

function extract_archive($file, $dir)
{
	exec("7zip e $file -o$dir -y -aoa", NULL, $return_value);
	return $return_value;
}
?>