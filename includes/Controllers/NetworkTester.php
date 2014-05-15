<?php

namespace Controllers;
use Admin as AdminConfig;
use PricePoller;
use JSON;

class NetworkTester implements Controller {
	public function execute(array $matches, $url, $rest) {
		$fn = '/tmp_disk/price.json';
		
		if (!file_exists($fn)) {
			echo JSON::encode([]);
			return true;
		}
		
		$cached = JSON::decode(file_get_contents($fn));
		
		if (isset($cached['error']) && $cached['error']) {
			echo JSON::encode(['error' => true]);
			flush();
			$admin = AdminConfig::volatileLoad();
			unlink($fn);
			$pricingProvider = $admin->getConfig()->getPricingProvider();
			$pp = new PricePoller($pricingProvider);
			return true;
		}
		
		echo JSON::encode([]);
		return true;
	}
}
