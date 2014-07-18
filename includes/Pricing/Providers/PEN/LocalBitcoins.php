<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;

class LocalBitcoins implements PricingProvider {
	use SimplePricer;
	
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	private function fetchPrice($ticker) {
		if (!isset($ticker['PEN']['rates']['last'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['PEN']['rates']['last']);
	}
	
	public function getTickerURL() {
		return 'https://localbitcoins.com/bitcoinaverage/ticker-all-currencies/';
	}
}

