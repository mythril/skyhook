<?php

namespace Pricing\Traits;

use PricingProvider;
use ServiceLocater;
use Amount;

trait Modifier {
	private $value;
	private $pricingProvider;
	
	public function configure(array $options) {
		$delegate = $options['children'][0];
		if (!isset($options['value'])) {
			throw new \ConfigurationException($this->missingMessage());
		}
		
		$this->value = new Amount($options['value']);
		
		$this->pricingProvider = ServiceLocater::resolve(
			$delegate,
			'PricingProvider'
		);
		
		$this->pricingProvider->configure($delegate);
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function getDelegators() {
		return [$this->pricingProvider];
	}
	
	public function isConfigured() {
		return $this->pricingProvider->isConfigured();
	}
	
	public function getSupportedCurrencies() {
		return $this->pricingProvider->getSupportedCurrencies();
	}
	
	public function getPrice() {
		$price = $this->pricingProvider->getPrice();
		return $this->modify($price, $this->value);
	}
}



