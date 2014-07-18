<?php

namespace Currencies;
use Amount;

class MYR implements FiatMeta {
	use Common;
	
	public function getDenominations() {
		return [
			1 => new Amount('1'),
			2 => new Amount('5'),
			3 => new Amount('10'),
		];
	}
}

