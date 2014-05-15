<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;
use Pricing\Traits\SimplePricer;

class VaultOfSatoshi implements PricingProvider {
	use SimplePricer;

	private function fetchPrice($ticker) {
		if (!isset($ticker['data'][0]['price']['value'])) {
			throw new \UnexpectedValueException('Could not retrieve pricing data.');
		}
		
		return new Amount($ticker['data'][0]['price']['value']);
	}

	private function getTickerURL() {
		return 'https://api.vaultofsatoshi.com/public/recent_transactions?' . http_build_query([
			'order_currency' => 'BTC',
			'payment_currency' => 'CAD',
			'count' => 1,
		]);
	}
}

