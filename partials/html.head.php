<head>
	<meta charset='UTF-8'/>
	<title><?php echo $template['page_title']; ?> | libba.net</title>
<?php foreach ($template['styles'] AS $style) { ?>
	<link rel='stylesheet' type='text/css' href='<?php echo $style; ?>'/>
<?php }
      foreach ($template['scripts'] AS $script) { ?>
	<script type='text/javascript' src='<?php echo $script; ?>'></script>
<?php } ?>
</head>