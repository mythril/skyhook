<?php

namespace Pricing\Traits;
use SimpleHTTP;

trait SimplePricer {
	public function configure(array $options) {
		return;
	}
	
	public function isConfigured() {
		return true;
	}
	
	public function getPrice() {
		$ticker = \JSON::decode(
			SimpleHTTP::get(
				$this->getTickerURL()
			)
		);
		
		return $this->fetchPrice($ticker);
	}
}

