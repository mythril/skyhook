<?php

namespace Currencies;
use Amount;

class AED implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'د.إ';
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
}

