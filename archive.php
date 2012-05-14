<?php
function archive_extract($archive, $dir)
{
	exec("7za e ".escapeshellarg($archive)." -o".escapeshellarg($dir)." -y -aoa", $arr, $return_value);
	return $return_value;
}

function archive_extract_file($archive, $file, $dir)
{
	exec("7za e ".escapeshellarg($archive)." -y -aoa -o".escapeshellarg($dir)." ".escapeshellarg($file), $arr, $return_value);
	return $return_value;
}
?>