<div class="error-message">
	<div class="message"><?php echo $error_message; ?></div>
	<?php if (isset($error_description)) { ?>
		<div class="description">
			<?php echo $error_description; ?>
		</div>
	<?php } ?>
</div>