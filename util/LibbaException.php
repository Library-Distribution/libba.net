<?php
class LibbaException extends Exception {
	protected $description;
	protected $type;

	public function __construct($msg, $descr, $type = 'error') {
		parent::__construct($msg);
		$this->description = $descr;
		$this->type = $type;
	}

	public function getDescription() {
		return $this->description;
	}

	public function getType() {
		return $this->type;
	}
}