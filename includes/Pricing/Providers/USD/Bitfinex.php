<?php

namespace PricingProviders;

use PricingProvider;
use SimpleHTTP;
use JSON;
use Amount;

class Bitfinex implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			SimpleHTTP::get(
				'https://api.bitfinex.com/v1/ticker/btcusd'
			)
		);
		
		if (!isset($ticker['last_price'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['last_price']);
	}
}

