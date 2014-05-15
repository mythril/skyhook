<?php

namespace Controllers;
use JSON;
use Template;
use Admin as AdminConfig;

class ConnectivityChecker implements Controller {
	public function execute(array $matches, $url, $rest) {
		$fn = '/tmp_disk/price.json';
		if (!file_exists($fn)) {
			return false;
		}
		$priceData = JSON::decode(file_get_contents($fn));
		
		if (isset($priceData['error']) && $priceData['error']) {
			$admin = AdminConfig::volatileLoad();
			$contactInfo = $admin->getConfig()->getContactInformation();
			$tmpl = new Template('network-out');
			$tmpl->render([
				'contactInfo' => $contactInfo
			]);
			return true;
		}
		
		return false;
	}
}


