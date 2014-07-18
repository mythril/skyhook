<?php

namespace Currencies;
use Amount;

class HNL implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'L';
	}
	
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

