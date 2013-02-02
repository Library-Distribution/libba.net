<?php
function error($message, $description = NULL, $clean = false) {
	_message('error', $message, $description, $clean);
}

function warning($message, $description = NULL, $clean = false) {
	_message('warning', $message, $description, $clean);
}

function _message($type, $message, $description = NULL, $clean = false) {
?>
<div class="<?php echo $type; ?>">
	<div class="message"><img alt='' src='images/<?php echo $type; ?>.png'/> <?php echo ucfirst($type); ?>: <?php echo $clean ? htmlentities($message) : $message; ?></div>
	<?php if ($description !== NULL) { ?>
		<div class="description">
			<?php echo $clean ? htmlentities($description) : $description; ?>
		</div>
	<?php } ?>
</div>
<?php
}
?>