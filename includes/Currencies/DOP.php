<?php

namespace Currencies;
use Amount;

class DOP implements FiatMeta {
	use Common;
	
	public function getDenominations() {
		return [
			1 => new Amount('100'),
			2 => new Amount('200'),
			3 => new Amount('500'),
			4 => new Amount('1000'),
			5 => new Amount('2000'),
		];
	}
}

