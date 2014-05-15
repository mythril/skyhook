<?php

namespace Currencies;
use Amount;

class MXN implements FiatMeta {
	use Common;
	
	public function getDenominations() {
		return [
			1 => new Amount('20'),
			2 => new Amount('50'),
			3 => new Amount('100'),
			4 => new Amount('200'),
			5 => new Amount('500'),
		];
	}
}

