<?php

namespace Currencies;
use Amount;

class HTG implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'G';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('100'),
			2 => new Amount('250'),
			3 => new Amount('500'),
			4 => new Amount('1000'),
		];
	}
}

