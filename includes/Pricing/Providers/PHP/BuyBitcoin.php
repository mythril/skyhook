<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;

class BuyBitcoin implements PricingProvider {
	use SimplePricer;
	
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	private function fetchPrice($ticker) {
		if (!isset($ticker['sell_price'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['sell_price']);
	}
	
	public function getTickerURL() {
		return 'https://buybitcoin.ph/api/ticker';
	}
}
