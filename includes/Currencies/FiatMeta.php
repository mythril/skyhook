<?php
namespace Currencies;

use Amount;

interface FiatMeta extends CurrencyMeta {
	public function getDenominations();
}

