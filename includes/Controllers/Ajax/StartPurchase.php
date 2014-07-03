<?php

namespace Controllers\Ajax;
use Container;
use Admin as AdminConfig;
use Amount;
use Config;
use Template;
use DB;
use BitcoinAddress;
use BillScanner;
use Purchase;
use Controllers\Controller;
use JSON;
use Exception;

class StartPurchase implements Controller {
	private function start($unvalidated) {
		$addr = new BitcoinAddress($unvalidated);
		$admin = AdminConfig::volatileLoad();
		$cfg = $admin->getConfig();
		
		$ticket = Purchase::create(
			$cfg,
			Container::dispense('DB'),
			$addr
		);
		
		$isZero = $ticket->getCurrencyAmount()->isEqualTo(new Amount("0"));
		
		if (($ticket->getStatus() !== Purchase::PENDING)
		|| ($isZero === false)) {
			throw new Exception("That purchase was already started.");
		}
		
		$scanner = Container::dispense('BillScanner');
		if ($scanner->isRunning()) {
			$scanner->stop();
		}
		$scanner->start();
		$price = $ticket->getBitcoinPrice();
		return [
			'ticketId' => $ticket->getId(),
			'price' => [
				'value' => $price->get(),
				'formatted' => $cfg->getCurrencyMeta()->format($price, true),
			],
		];
	}
	
	public function execute(array $matches, $url, $rest) {
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		try {
			echo JSON::encode($this->start($matches['address']));
			return true;
		} catch (Exception $e) {
			echo JSON::encode(['error' => $e->getMessage()]);
			return true;
		}
	}
}

