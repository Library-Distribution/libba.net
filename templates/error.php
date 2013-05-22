<!DOCTYPE html>
<html>
	<?php $template['page_title'] = ucfirst($template['type']); ?>
	<?php require dirname(__FILE__) . '/../partials/html.head.php'; ?>
	<?php require dirname(__FILE__) . '/../partials/html.body.upper.php'; ?>

	<div class="<?php echo $template['type']; ?>">
		<div class="message"><img alt='' src='images/<?php echo $template['type']; ?>.png'/> <?php echo ucfirst($template['type']); ?>: <?php echo $template['msg']; ?></div>
		<?php if (!empty($template['description'])) { ?>
			<div class="description">
				<?php echo $template['description']; ?>
			</div>
		<?php } ?>
	</div>

	<?php require dirname(__FILE__) . '/../partials/html.body.lower.php'; ?>
</html>