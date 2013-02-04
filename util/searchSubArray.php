<?php
# SOURCE: http://stackoverflow.com/a/6303043
# modified to allow an array of key-value pairs instead of just one
function searchSubArray(Array $array, $key_values)
{
	foreach ($array as $index => $subarray)
	{
		$match = true;
		foreach ($key_values AS $key => $value)
		{
			$match = $match && (isset($subarray[$key]) && $subarray[$key] == $value);
		}
		if ($match)
			return $index;
	}
}
?>