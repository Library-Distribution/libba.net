<div class="error-message ui-state-error ui-corner-all">
	<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><div class="message"><?php echo $error_message; ?></div>
	<?php if (isset($error_description)) { ?>
		<div class="description">
			<?php echo htmlentities($error_description); ?>
		</div>
	<?php } ?>
</div>