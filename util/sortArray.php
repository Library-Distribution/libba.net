<?php
# This function was taken from http://www.the-art-of-web.com/php/sortarray/ (2012/06/06) and has been created by Thomas Heuer (Germany). Thanks a lot for this!
#It has been modified by me (maul.esel) to use objects instead of arrays, to allow both ascending and descending sorting and to work without anonymous functions.
function sortArray($data, $field)
{
	if (!is_array($field))
	{
		$field = array($field => false);
	}
	sortArray_storage($field);
	usort($data, 'sortArray_sort');
	return $data;
}

function sortArray_sort($a, $b)
{
	$retval = 0;
	$fields = sortArray_storage();
	foreach($fields as $fieldname => $direction)
	{
		if($retval == 0)
		{
			if (!$direction)
				$retval = strnatcasecmp($a[$fieldname], $b[$fieldname]);
			else
				$retval = strnatcasecmp($b[$fieldname], $a[$fieldname]);
		}
	}
	return $retval;
}

function sortArray_storage($fields = NULL)
{
	static $storage = NULL;
	if ($fields != NULL)
		$storage = $fields;
	return $storage;
}
?>