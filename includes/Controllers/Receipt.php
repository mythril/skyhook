<?php

namespace Controllers;
use Template;
use Container;
use Purchase;
use Admin as AdminConfig;

class Receipt implements Controller {
	public function execute(array $matches, $url, $rest) {
		$get = Container::dispense('Environment\Get');
		$cfg = AdminConfig::volatileLoad()->getConfig();
		$db = Container::dispense('DB');
		
		$p = Purchase::load(
			$cfg,
			$db,
			$matches['ticket']
		);
		
		$scanner = Container::dispense('BillScanner');
		$scanner->stop();
		
		$error = false;
		
		if (!empty($get['error'])) {
			$error = true;
		}
		
		$sent = false;
		
		if (!empty($get['sent'])) {
			$sent = true;
		}
		
		$tmpl = new Template('receipt');
		$tmpl->render([
			'purchase' => $p,
			'config' => $cfg,
			'error' => $error,
			'sent' => $sent,
		]);
		return true;
	}
}

