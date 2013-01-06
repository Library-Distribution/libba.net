<?php
	require_once(dirname(__FILE__) . "/subfolder.php");

	function rewrite()
	{
		$haystack = ob_get_contents();
		$prefix = get_subfolder_prefix();

		$rewritten = preg_replace("/(href|src)=(\"|')(?!(mailto|ftp|https?|\/\/|\?|#|\.\/))(.+)(\"|')/isU", "$1=\"$prefix$4\"", $haystack);
		ob_clean();
		return $rewritten;
	}
?>