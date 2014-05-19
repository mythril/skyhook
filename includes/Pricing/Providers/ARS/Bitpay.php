<?php

namespace PricingProviders;

use PricingProvider;
use SimpleHTTP;
use JSON;
use Amount;

class Bitpay implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			SimpleHTTP::get(
				'https://bitpay.com/api/rates/ARS'
			)
		);
		
		if (!isset($ticker['rate'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['rate']);
	}
}

