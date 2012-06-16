<?php
# This function was taken from http://www.the-art-of-web.com/php/sortarray/ (2012/06/06) and has been created by Thomas Heuer (Germany). Thanks a lot for this!
#It has been modified by me (maul.esel) to use objects instead of arrays and to allow both ascending and descending sorting.
function sortArray($data, $field)
{
	if (!is_array($field))
	{
		$field = array($field => false);
	}
	usort($data, function($a, $b) use($field)
	{
		$retval = 0;
		foreach($field as $fieldname => $direction)
		{
			if($retval == 0)
			{
				if (!$direction)
					$retval = strnatcasecmp($a->$fieldname, $b->$fieldname);
				else
					$retval = strnatcasecmp($b->$fieldname, $a->$fieldname);
			}
		}
		return $retval;
	});
	return $data;
}
?>