<?php

namespace Pricing\Modifiers;

use PricingProvider;
use PriceModifier;
use Pricing\Traits\Modifier;
use ServiceLocater;
use Amount;

class PercentageFee implements PriceModifier {
	use Modifier;
	
	public function missingMessage() {
		return 'Percentage fee not supplied.';
	}
	
	public function modify(Amount $price, Amount $value) {
		$factor = $value
			->divideBy(new Amount('100'))
			->add(new Amount('1'));
		return $price->multiplyBy($factor);
	}
}



