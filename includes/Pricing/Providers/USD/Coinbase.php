<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;

class Coinbase implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			file_get_contents(
				'https://coinbase.com/api/v1/currencies/exchange_rates'
			)
		);
		
		$key = 'btc_to_usd';
		
		if (!isset($ticker[$key])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker[$key]);
	}
}

