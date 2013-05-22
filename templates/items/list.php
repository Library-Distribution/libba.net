<!DOCTYPE html>
<html>
	<?php require dirname(__FILE__) . '/../../partials/html.head.php'; ?>
	<?php require dirname(__FILE__) . '/../../partials/html.body.upper.php'; ?>

	<div id='items-list' class='js-ui-accordion'>
	<?php foreach ($template['items'] AS $letter => $items) { ?>
		<div class='letter-container' id='items<?php echo $letter; ?>'>
			<h3 class='js-ui-accordion-header'><?php echo $letter; ?></h3>
			<div id='items_<?php echo $letter; ?>'>
				<ul>
				<?php foreach ($items AS $item) { ?>
					<li id='item<?php echo $item['id']; ?>' class='<?php echo $item['type']; ?>'>
						<a class='item' href='./<?php echo $item['id']; ?>'><?php echo $item['name']; ?></a>
						(v<?php echo $item['version']; ?>) by
						<a class='userlink' href='users/<?php echo $item['user']['name']; ?>/profile'><?php echo $item['user']['name']; ?></a>
					<?php if (!$item['reviewed']) { ?>
						<a title='This item has not yet been reviewed' href='reviews/<?php echo $item['id']; ?>' class='unreviewed'></a>
					<?php } ?>
					</li>
				<?php } ?>
				</ul>
			</div>
		</div>
	<?php } ?>
	</div>

	<?php require dirname(__FILE__) . '/../../partials/html.body.lower.php'; ?>
</html>