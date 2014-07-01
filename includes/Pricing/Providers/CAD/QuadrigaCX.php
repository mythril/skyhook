<?php

namespace PricingProviders;

use PricingProvider;
use SimpleHTTP;
use JSON;
use Amount;

class QuadrigaCX implements PricingProvider {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = JSON::decode(
			SimpleHTTP::get(
				'https://api.quadrigacx.com/public/trades?book=btc_cad'
			)
		);
		
		$result = array_reduce($ticker, function ($prev, $comp) {
			if ($prev['datetime'] > $comp['datetime']) {
				return $prev;
			}
			return $comp;
		}, ['datetime' => '0']);
		
		return new Amount($result['rate']);
	}
}

