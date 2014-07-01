<?php

namespace PricingProviders;

use PricingProvider;
use SimpleHTTP;
use JSON;
use Amount;

class CoinJar implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			SimpleHTTP::get(
				'https://coinjar-data.herokuapp.com/fair_rate.json'
			)
		);
		
		if (!isset($ticker['spot']['AUD'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['spot']['AUD']);
	}
}

