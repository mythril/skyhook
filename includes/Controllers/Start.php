<?php

namespace Controllers;

use Template;
use Admin as AdminConfig;

class Start implements Controller {
	use ScannerStopper;
	
	public function execute(array $matches, $url, $rest) {
		$this->stopScanner();
		$admin = AdminConfig::volatileLoad();
		$contactInfo = $admin->getConfig()->getContactInformation();
		
		$tmpl = new Template('start');
		$tmpl->render([
			'contactInfo' => $contactInfo,
		]);
		return true;
	}
}

