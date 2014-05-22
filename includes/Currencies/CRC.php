<?php

namespace Currencies;
use Amount;

class CRC implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return '₡';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('1000'),
			2 => new Amount('2000'),
			3 => new Amount('5000'),
			4 => new Amount('10000'),
			5 => new Amount('20000'),
		];
	}
}

