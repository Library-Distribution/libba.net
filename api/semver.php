<?php
function semver_validate($version)
{
	return !!preg_match('/^(\d+)\.(\d+)\.(\d+)(\-([0-9A-Za-z\-]+\.)*([0-9A-Za-z\-]+))?(\+([0-9A-Za-z\-]+\.)*([0-9A-Za-z\-]+))?$/', $version);
}
function semver_parts($version, &$parts)
{
	return !!preg_match('/^(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)(\-\K)?(?P<prerelease>([0-9A-Za-z\-]+\.)*([0-9A-Za-z\-]+))?(\+\K)?(?P<build>([0-9A-Za-z\-]+\.)*([0-9A-Za-z\-]+))?$/', $version, $parts);
}
function semver_compare($version1, $version2)
{
	if (!semver_parts($version1, $parts1))
		throw new Exception("Invalid version!");
	if (!semver_parts($version2, $parts2))
		throw new Exception("Invalid version!");

	foreach (array("major", "minor", "patch") AS $part)
	{
		$part1 = (int)$parts1[$part]; $part2 = (int)$parts2[$part]; # convert to numbers
		if ($part1 < $part2)
			return -1;
		else if ($part1 > $part2)
			return +1;
	}

	foreach (array("prerelease" => +1, "build" => -1) AS $part => $ret)
	{
		if (!empty($parts1[$part]) && !empty($parts2[$part]))
		{
			$part_list1 = explode(".", $parts1[$part]);
			$part_list2 = explode(".", $parts2[$part]);

			for ($index = 0; $index < min(count($part_list1), count($part_list1)); $index++) # use the smaller amount of parts
			{
				$part1 = $part_list1[$index]; $part2 = $part_list2[$index];
				if (ctype_digit($part1) && ctype_digit($part2))
				{
					$part1 = (int)$part1; $part2 = (int)$part2; # convert to numbers
					if ($part1 < $part2)
						return -1;
					else if ($part1 > $part2)
						return +1;
					continue;
				}
				# at least one is non-numeric: compare by characters
				if ($part1 < $part2)
					return -1;
				else if ($part1 > $part2)
					return +1;
			}
			# all compared parts were equal - the longer one wins
			if (count($part_list1) < count($part_list2))
				return -1;
			else if (count($part_list1) > count($part_list2))
				return +1;
		}
		else if (empty($parts1[$part]) && !empty($parts2[$part])) # version1 has no prerelease -> version1 is higher | version1 has no build -> version1 is lower
			return $ret;
		else if (empty($parts2[$part]) && !empty($parts1[$part])) # version2 has no prerelease -> version2 is higher | version2 has no build -> version2 is lower
			return -$ret;
	}

	return 0;
}
?>