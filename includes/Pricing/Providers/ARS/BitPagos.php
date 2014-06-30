<?php

namespace PricingProviders;

use PricingProvider;
use SimpleHTTP;
use JSON;
use Amount;

class BitPagos implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			SimpleHTTP::get(
				'https://www.bitpagos.net/api/v1/rates/BTC/'
			)
		);
		
		if (!isset($ticker['rates'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['rates']['ARS']);
	}
}

