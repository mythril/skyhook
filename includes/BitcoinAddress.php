<?php

class BitcoinAddress {
	private $addr;
	
	public function __construct($addr) {
		if (!AddressUtility::checkAddress($addr)) {
			throw new InvalidArgumentException("Invalid Bitcoin address.");
		}
		$this->addr = $addr;
	}
	
	public function get() {
		return $this->addr;
	}
}


