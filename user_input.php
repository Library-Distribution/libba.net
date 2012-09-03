<?php
	require_once("markdown/markdown.php");
	require_once("smartypants/smartypants.php");
	require_once("htmlpurifier/library/HTMLPurifier.auto.php");
	
	function user_input_process($raw)
	{
		static $purifier, $purifier_config;

		if (!$purifier_config)
		{
			$purifier_config = HTMLPurifier_Config::createDefault();
			# $purifier_config->set('HTML', 'Doctype', 'HTML'); # not yet supported
			# todo: URL filter
		}
		if (!$purifier)
		{
			$purifier = new HTMLPurifier($purifier_config);
		}

		$text = SmartyPants(Markdown($raw));
		$text = $purifier->purify($text);
		return $text;
	}

	function user_input_clean($raw)
	{
		return strip_tags(user_input_process($raw), "<em><strong><a><h1><h2><h3><h4><h5>");
	}
?>