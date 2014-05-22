<?php

namespace Controllers;
use Admin as AdminConfig;
use PricePoller;

class Price implements Controller {
	use Comet;
	
	private function interval() {
		$SECONDS = 1000000;
		return 60 * $SECONDS;
	}
	
	public function execute(array $matches, $url, $rest) {
		$admin = AdminConfig::volatileLoad();
		$pricingProvider = $admin->getConfig()->getPricingProvider();
		$this->drain(new PricePoller($pricingProvider));
		return true;
	}
}

