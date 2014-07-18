<?php

namespace Currencies;
use Amount;

class QAR implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'ر.ق';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('1'),
			2 => new Amount('5'),
			3 => new Amount('10'),
			4 => new Amount('50'),
		];
	}
}

