<?php

namespace Controllers;
use Container;
use Config;
use DB;
use Admin as AdminConfig;
use BitcoinAddress;
use Purchase;

class StartPurchase implements Controller {
	public function execute(array $matches, $url, $rest) {
		$addr = new BitcoinAddress($matches['address']);
		$admin = AdminConfig::volatileLoad();
		$cfg = $admin->getConfig();
		
		$ticket = Purchase::create(
			$cfg,
			Container::dispense('DB'),
			$addr
		);
		
		header('HTTP/1.1 303 See Other');
		header('Location: /purchase/' . $addr->get() . '/' . $ticket->getId());
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		return true;
	}
}

