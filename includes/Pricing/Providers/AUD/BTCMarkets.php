<?php

namespace PricingProviders;

use PricingProvider;
use SimpleHTTP;
use JSON;
use Amount;

class BTCMarkets implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			SimpleHTTP::get(
				'https://api.btcmarkets.net/market/BTC/AUD/tick'
			)
		);
		
		if (!isset($ticker['lastPrice'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['lastPrice']);
	}
}

