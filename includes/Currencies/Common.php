<?php

namespace Currencies;
use Amount;

trait Common {
	
	public function getDecimalPlaces() {
		return 2;
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('5'),
			2 => new Amount('10'),
			3 => new Amount('20'),
			4 => new Amount('50'),
			5 => new Amount('100'),
		];
	}
	
	public function format(Amount $amt, $withSymbol = true) {
		if ($withSymbol) {
			return $this->getSymbol() . bcadd('0', $amt->get(), $this->getDecimalPlaces());
		} else {
			return bcadd('0', $amt->get(), $this->getDecimalPlaces());
		}
	}
	
	public function getSymbol() {
		return '$';
	}
	
	public function getISOCode() {
		$blah = explode("\\", get_class($this));
		return array_pop($blah);
	}
}
