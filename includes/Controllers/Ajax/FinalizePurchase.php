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
use Purchase;
use BillScannerDriver;

class FinalizePurchase implements Controller {
	use BalanceCacheUpdater;
	
	public function execute(array $matches, $url, $rest) {
		$admin = Admin::volatileLoad();
		$cfg = $admin->getConfig();
		$db = Container::dispense('DB');
		$scanner = new BillScannerDriver();
		
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
		
		$scanner->stop();
		
		try {
			Purchase::completeTransaction($cfg, $db, $ticket);
			$this->notifyBalanceChange();
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
			$this->notifyBalanceChange();
		}
		
		echo JSON::encode($response);
		
		return true;
	}
}
