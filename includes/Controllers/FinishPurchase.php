<?php

namespace Controllers;
use Container;
use Admin as AdminConfig;
use Amount;
use Config;
use Template;
use DB;
use BitcoinAddress;
use Purchase;

class FinishPurchase implements Controller {
	use ScannerStopper;
	public function execute(array $matches, $url, $rest) {
		$this->stopScanner();
		$addr = new BitcoinAddress($matches['address']);
		$admin = AdminConfig::volatileLoad();
		$cfg = $admin->getConfig();
		
		$ticket = Purchase::load(
			$cfg,
			Container::dispense('DB'),
			intval($matches['ticket'])
		);
		
		if (strval($addr->get()) !== strval($ticket->getCustomerAddress()->get())) {
			error_log("Tampering detected on ticket #" . $ticket->getId() . ' at ' . time());
			return false;
		}
		
		$isZero = $ticket->getCurrencyAmount()->isEqualTo(new Amount("0"));
		
		if (($ticket->getStatus() !== Purchase::PENDING) || ($isZero === false)) {
			header('HTTP/1.1 303 See Other');
			header('Location: /start');
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			return false;
		}
		
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		$tmpl = new Template('purchase');
		$tmpl->render([
			'ticket' => $ticket,
			'config' => $cfg,
		]);
		return true;
	}
}
