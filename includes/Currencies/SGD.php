<?php

namespace Currencies;
use Amount;

class SGD implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'S$';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('2'),
			2 => new Amount('5'),
			3 => new Amount('10'),
		];
	}
}

