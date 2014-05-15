<?php
namespace Currencies;

use Amount;

interface CurrencyMeta {
	public function format(Amount $amt, $withSymbol = false);
	public function getSymbol();
	public function getISOCode();
}

