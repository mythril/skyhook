<?php

namespace Currencies;
use Amount;

class JMD implements FiatMeta {
	use Common;
	
	public function getDenominations() {
		return [
			1 => new Amount('50'),
			2 => new Amount('100'),
			3 => new Amount('500'),
			4 => new Amount('1000'),
		];
	}
}

