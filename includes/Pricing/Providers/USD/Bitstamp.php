<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;

class Bitstamp implements PricingProvider {
	use SimplePricer;

	private function fetchPrice($ticker) {
		if (!isset($ticker['last'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['last']);
	}
	
	private function getTickerURL() {
		return 'https://www.bitstamp.net/api/ticker/';
	}
}

