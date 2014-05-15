<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;


class Kraken implements PricingProvider {
	use SimplePricer;

	private function fetchPrice($ticker) {
		if (!isset($ticker['result']['XXBTZEUR']['c'][0])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['result']['XXBTZEUR']['c'][0]);
	}
	
	private function getTickerURL() {
		return 'https://api.kraken.com/0/public/Ticker?pair=XXBTZEUR';
	}
}

