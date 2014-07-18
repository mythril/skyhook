<?php

namespace Currencies;
use Amount;

class TRL implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		//http://www.fileformat.info/info/unicode/char/20ba/index.htm
		return html_entity_decode('&#8378;');
	}
	
	public function getDenominations() {
		return [
			1 => new Amount('5'),
			2 => new Amount('10'),
			3 => new Amount('20'),
		];
	}
}

