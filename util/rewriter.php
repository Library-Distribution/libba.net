<?php
	require_once(dirname(__FILE__) . "/subfolder.php");

	function rewrite($content = NULL)
	{
		$haystack = $content !== NULL ? $content : ob_get_contents();
		$prefix = get_subfolder_prefix();

		$rewritten = preg_replace("/(href|src)=(\"|')(?!(mailto|ftp|https?|\/\/|\?|#|\.\/))(.+)(\"|')/isU", "$1=\"$prefix$4\"", $haystack);
		if ($content === NULL) {
			ob_clean();
		}
		return $rewritten;
	}
?>