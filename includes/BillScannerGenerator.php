<?php

class BillScannerGenerator implements Iterator {
	private $scanner;
	public function __construct(BillScanner $b) {
		$this->scanner = $b;
	}
	
	private $current;
	public function current() {
		if (!isset($this->current)) {
			$this->next();
		}
		return $this->current;
	}
	
	public function key() {
		return 0;
	}
	
	public function next() {
		$this->current = $this->scanner->getBalance();
	}
	
	public function rewind() {
		//doesn't do anything
	}
	
	public function valid() {
		return $this->scanner->isRunning();
	}
}
