<?php
ob_start();
session_start();

require_once dirname(__FILE__) . '/Layout.php';

$layout = new Layout($layout);
try {
	$data = logic();
} catch (LibbaException $e) {
	$layout = new Layout('error');
	$data = array('msg' => $e->getMessage(), 'description' => $e->getDescription(), 'type' => $e->getType());
}

ob_end_clean();

$layout->evaluate($data);
$layout->rewrite();
$layout->output();
?>