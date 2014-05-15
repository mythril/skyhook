<?php

namespace Currencies;
use Amount;

class EUR implements FiatMeta {
	use Common;
	
	public function getSymbol() {
		return '€';
	}
}

