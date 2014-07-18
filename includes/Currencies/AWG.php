<?php

namespace Currencies;
use Amount;

class AWG implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'ƒ';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('10'),
			2 => new Amount('25'),
			3 => new Amount('50'),
			4 => new Amount('100'),
		];
	}
}

