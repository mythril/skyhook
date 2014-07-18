<?php

namespace PricingProviders;

use PricingProvider;
use JSON;
use Amount;

class StaticPrice implements PricingProvider {
	private $price;
	
	public function configure(array $options) {
		if (!isset($options['value'])) {
			throw new \ConfigurationException("StaticPrice: price is not set.");
		}
		$this->price = new Amount($options['value']);
	}
	
	public function isConfigured() {
		return isset($this->price);
	}
	
	public function getPrice() {
		return $this->price;
	}
}

