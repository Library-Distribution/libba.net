<?php
	require_once("markdown/markdown.php");
	require_once("smartypants/smartypants.php");
	require_once("htmlpurifier/library/HTMLPurifier.auto.php");
	
	function user_input_process($raw)
	{
		$text = SmartyPants(Markdown($raw));
		# todo: purify
		return $text;
	}

	function user_input_clean($raw)
	{
		return strip_tags(user_input_process($raw), "<em><strong><a><h1><h2><h3><h4><h5>");
	}
?>