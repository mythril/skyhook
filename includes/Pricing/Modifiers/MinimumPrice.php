<?php

namespace Pricing\Modifiers;

use PricingProvider;
use PriceModifier;
use Pricing\Traits\Modifier;
use ServiceLocater;
use Amount;

class MinimumPrice implements PriceModifier {
	use Modifier;
	
	public function missingMessage() {
		return 'Minimum value not supplied.';
	}
	
	public function modify(Amount $a, Amount $b) {
		if ($a->isGreaterThan($b)) {
			return $a;
		}
		return $b;
	}
}



