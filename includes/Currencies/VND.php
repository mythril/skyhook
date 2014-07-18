<?php

namespace Currencies;
use Amount;

class VND implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return '₫';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('100000'),
			2 => new Amount('200000'),
			3 => new Amount('500000'),
		];
	}
}

