<?php

namespace Controllers\Ajax;
use Exception;
use Exceptions\InsufficientFundsException;
use JobManager;
use Controllers\Controller;
use Container;
use Admin;
use Amount;
use Config;
use JSON;
use Template;
use DB;
use BitcoinAddress;
use BillScanner;
use Purchase;

class FinalizePurchase implements Controller {
	public function execute(array $matches, $url, $rest) {
		$admin = Admin::volatileLoad();
		$cfg = $admin->getConfig();
		$db = Container::dispense('DB');
		$scanner = new BillScanner();
		
		$ticket = Purchase::load($cfg, $db, intval($matches['ticket']));
		$response = [];
		
		$currencyEntered = $ticket->getCurrencyAmount();
		$zero = new Amount("0");
		
		if ($currencyEntered->isEqualTo($zero)
		|| $currencyEntered->isLessThan($zero)) {
			echo JSON::encode(['cancelTransaction' => true]);
			return true;
		}
		
		if ($ticket->getStatus() !== Purchase::PENDING) {
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			echo JSON::encode(['redirect' => '/start']);
			return true;
		}
		
		if ($scanner->isRunning()) {
			$scanner->stop();
		}
		
		try {
			Purchase::completeTransaction($cfg, $db, $ticket);
			$response['proceed'] = true;
		} catch (Exception $e) {
			if ($e instanceof InsufficientFundsException) {
				$response['insufficient'] = true;
			}
			file_put_contents(
				'/home/pi/phplog/purchase_errors.' . intval($matches['ticket']) . '.log',
				$e,
				FILE_APPEND
			);
			$response['error'] = true;
			JobManager::enqueue(
				$db,
				'PurchaseError',
				['purchase_id' => $ticket->getId()]
			);
		}
		
		echo JSON::encode($response);
		
		return true;
	}
}
