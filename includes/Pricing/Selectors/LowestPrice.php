<?php

namespace Pricing\Selectors;

use PricingProvider;
use ServiceLocater;
use Amount;

class LowestPrice implements \PricingProxy {
	private $pricingProviders = [];
	
	public function getDelegators() {
		return $this->pricingProviders;
	}
	
	public function configure(array $options) {
		foreach ($options['children'] as $delegate) {
			$pricingProvider = ServiceLocater::resolve(
				$delegate,
				'PricingProvider'
			);
			
			$pricingProvider->configure($delegate);
			$this->pricingProviders[] = $pricingProvider;
		}
		
		if (count($this->pricingProviders) === 0 && !isset($options['value'])) {
			throw new \ConfigurationException("No pricing sources to choose from");
		}
	}
	
	public function isConfigured() {
		$configured = true;
		
		foreach ($this->pricingProviders as $provider) {
			if (!$provider->isConfigured()) {
				$configured = false;
				break;
			}
		}
		
		return $configured;
	}
	
	public function getSupportedCurrencies() {
		$supportedSets = [];
		
		foreach ($this->pricingProviders as $provider) {
			$supportedSets[] = $provider->getSupportedCurrencies;
		}
		
		return call_user_func_array('array_intersect_key', $supportedSets);
	}
	
	public function getPrice() {
		//copy array
		$providers = $this->pricingProviders;
		
		$first = array_shift($providers);
		$price = $first->getPrice();
		
		foreach ($providers as $provider) {
			$test = $provider->getPrice();
			if ($test->isLessThan($price)) {
				$price = $test;
			}
		}
		
		return $price;
	}
}



