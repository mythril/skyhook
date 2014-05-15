<?php

namespace Currencies;
use Amount;

class USD implements FiatMeta {
	use Common;
	
	public function getDenominations() {
		return [
			1 => new Amount('1'),
			2 => new Amount('2'),
			3 => new Amount('5'),
			4 => new Amount('10'),
			5 => new Amount('20'),
			6 => new Amount('50'),
			7 => new Amount('100'),
		];
	}
}

