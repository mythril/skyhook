<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;


class CampBX implements PricingProvider {
	use SimplePricer;

	private function fetchPrice($ticker) {
		if (!isset($ticker['Last Trade'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['Last Trade']);
	}
	
	private function getTickerURL() {
		return 'https://campbx.com/api/xticker.php';
	}
}

