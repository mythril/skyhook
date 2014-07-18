<?php

namespace Currencies;
use Amount;

class GTQ implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return 'Q';
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('1'),
			2 => new Amount('5'),
			3 => new Amount('10'),
			4 => new Amount('20'),
			5 => new Amount('50'),
			6 => new Amount('100'),
		];
	}
}

