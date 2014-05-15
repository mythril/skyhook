<?php

namespace Controllers;

use Template;
use Admin as AdminConfig;

class Account implements Controller {
	use ScannerStopper;
	
	public function execute(array $matches, $url, $rest) {
		$this->stopScanner();
		$admin = AdminConfig::volatileLoad();
		$contactInfo = $admin->getConfig()->getContactInformation();
		
		$tmpl = new Template('account');
		$tmpl->render([
			'contactInfo' => $contactInfo,
		]);
		return true;
	}
}

