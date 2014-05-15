<?php

namespace Controllers;
use Admin as AdminConfig;
use Amount;
use Purchase;
use Config;
use Math;
use Container;
use DB;
use BillScanner;
use Bill;
use BillScannerGenerator;

class BillScannerBalance implements Controller {
	use Comet;
	
	private $purchase;
	private $maxFiat;
	private $billScanner;
	private $currencyMeta;
	private $lowest;
	private $threshhold;
	private $denoms;
	
	/**
	 * Update the purchase ticket with the balance.
	 */
	public function beforeSend($data) {
		$purchase = $this->purchase;
		
		$oldAmount = $purchase->getCurrencyAmount();
		if ($data->isEqualTo($oldAmount) === false) {
			$purchase->setCurrencyAmount($data);
			$bill = $data->subtract($oldAmount);
			$db = Container::dispense('DB');
			$purchase->recalculateBitcoinAmount();
			Purchase::save(
				$db,
				$purchase
			);
			Bill::create(
				$db,
				$bill,
				$purchase
			);
		}
	}
	
	private function chooseBill(Amount $amt) {
		$bills = $this->denoms;
		$chosen = array_shift($bills);
		
		foreach ($bills as $bill) {
			if ($bill->isEqualTo($amt)) {
				return $bill;
			}
			if ($bill->isLessThan($amt)) {
				if ($bill->isGreaterThan($chosen)) {
					$chosen = $bill;
				}
			}
		}
		return $chosen;
	}
	
	private function intercept($data) {
		$result = [
			'bills' => $this->currencyMeta->format($data, false),
			'btc' => $this->purchase->getBitcoinAmount()->get(),
		];
		$diff = $this->maxFiat->subtract($this->purchase->getCurrencyAmount());
		if ($diff->isLessThan($this->threshhold)) {
			$result['diff'] = intval($this->chooseBill($diff)->get());
		}
		if ($diff->isLessThan($this->lowest)) {
			$this->billScanner->stop();
			$result['diff'] = 0;
		}
		return $result;
	}
	
	public function execute(array $matches, $url, $rest) {
		$admin = AdminConfig::volatileLoad();
		$config = $admin->getConfig();
		$this->currencyMeta = $config->getCurrencyMeta();
		
		$this->denoms = $this->currencyMeta->getDenominations();
		
		$this->threshhold = Math::max($this->denoms)[0]->multiplyBy(new Amount("2"));
		
		$this->lowest = Math::min($this->denoms)[0];
		
		$this->purchase = Purchase::load(
			$config,
			Container::dispense('DB'),
			intval($matches['ticket'])
		);
		$this->maxFiat = $config->getWalletProvider()
			->getBalance()
			->multiplyBy($this->purchase->getBitcoinPrice())
			->truncate();
		$maxTransaction = $config->getMaxTransactionValue();
		if ($maxTransaction->isGreaterThan(new Amount('0'))) {
			if ($maxTransaction->isLessThan($this->maxFiat)) {
				$this->maxFiat = $maxTransaction;
			}
		}
		$this->billScanner = new BillScanner();
		$this->drain(new BillScannerGenerator($this->billScanner));
		return true;
	}
}

