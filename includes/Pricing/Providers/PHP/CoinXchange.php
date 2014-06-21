<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;

class CoinXchange implements PricingProvider {
	use SimplePricer;
	
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	private function fetchPrice($ticker) {
		if (!isset($ticker['sell'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['sell']);
	}
	
	public function getTickerURL() {
		return 'https://www.coinxchange.ph/api/v1/quotes';
	}
}
