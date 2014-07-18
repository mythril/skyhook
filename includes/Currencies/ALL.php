<?php

namespace Currencies;
use Amount;

class ALL implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'L';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('200'),
			2 => new Amount('500'),
			3 => new Amount('1000'),
			4 => new Amount('2000'),
			5 => new Amount('5000'),
		];
	}
}

