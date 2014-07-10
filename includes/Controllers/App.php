<?php

namespace Controllers;

use Template;
use Admin as AdminConfig;

class App implements Controller {
	public function execute(array $matches, $url, $rest) {
		$admin = AdminConfig::volatileLoad();
		$contactInfo = $admin->getConfig()->getContactInformation();
		
		$tmpl = new Template('app');
		$tmpl->render([]);
		return true;
	}
}
