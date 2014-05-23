<?php

namespace Controllers;
use Admin as AdminConfig;
use Amount;
use Purchase;
use Config;
use Math;
use Container;
use DB;
use Bill;
use BillScannerDriver;
use JobManager;
use Localization;

class BillScannerBalance implements Controller {
	use Comet;
	
	private $db;
	private $purchase;
	private $maxFiat;
	private $billScanner;
	private $balance;
	private $currencyMeta;
	private $lowest;
	private $threshhold;
	private $denoms;
	private $driver;
	private $largestBill;
	private $config;
	
	public function __construct(DB $db, BillScannerDriver $driver) {
		$this->db = $db;
		$this->driver = $driver;
		$admin = AdminConfig::volatileLoad();
		$this->config = $admin->getConfig();
		$this->currencyMeta = $this->config->getCurrencyMeta();
		$this->denoms = $this->currencyMeta->getDenominations();
		$this->largest = Math::max($this->denoms);
		$this->threshhold = $this->largest->multiplyBy(new Amount("2"));
		$this->lowest = Math::min($this->denoms);
		$this->balance = new Amount("0");
		$maxTransaction = $this->config->getMaxTransactionValue();
		if ($maxTransaction->isGreaterThan(new Amount('0'))) {
			if ($maxTransaction->isLessThan($this->maxFiat)) {
				$this->maxFiat = $maxTransaction;
			}
		}
	}
	
	private function disableBills(Amount $max) {
		$bills = $this->denoms;
		$chosen = null;
		$disabled = [];
		
		foreach ($bills as $index => $bill) {
			if (!isset($chosen)) {
				$chosen = $bill;
			}
			if ($bill->isGreaterThan($max)) {
				$disabled[] = $index;
			}
			if ($bill->isEqualTo($max)) {
				$chosen = $bill;
			}
			if ($bill->isLessThan($max)) {
				if ($bill->isGreaterThan($chosen)) {
					$chosen = $bill;
				}
			}
		}
		
		$this->driver->disableBills($disabled);
		
		return $chosen;
	}
	
	private function getTotals() {
		$result = [
			'event' => 'totalsUpdated',
			'bills' => $this->currencyMeta->format($this->balance, false),
			'btc' => $this->purchase->getBitcoinAmount()->get(),
		];
		$diff = $this->maxFiat->subtract($this->balance);
		if ($diff->isLessThan($this->threshhold)) {
			$result['diff'] = intval($this->disableBills($diff)->get());
		}
		if ($diff->isLessThan($this->lowest)) {
			$this->driver->stop();
			$result['diff'] = 0;
		}
		return $result;
	}
	
	private function billInsertedHandler($billIndex) {
		if ($billIndex === 0) {
			return;
		}
		$bill = $this->denoms[$billIndex];
		$this->balance = $this->balance->add($bill);
		$this->purchase->setCurrencyAmount($this->balance);
		$this->purchase->recalculateBitcoinAmount();
		Purchase::save(
			$this->db,
			$this->purchase
		);
		$this->send($this->getTotals());
		Bill::create(
			$this->db,
			$bill,
			$this->purchase
		);
		if (connection_aborted()) {
			$this->driver->stop();
		}
	}
	
	private function getWalletBalance() {
		return $this->config
			->getWalletProvider()
			->getBalance()
			->multiplyBy($this->purchase->getBitcoinPrice())
			->truncate();
	}
	
	private function reportError($msg) {
		$i18n = Localization::getTranslator();
		return $i18n->_('This is an automated message.') . "\n\n"
			. sprintf($i18n->_('%s has encountered an error.'), $this->config->getMachineName()) . "\n\n"
			. $i18n->_('Time: ') . date('g:ia \o\n l jS F Y e') . "\n\n"
			. $i18n->_('Error Type: ' . $msg);
	}
	
	private function statusHandler(array $statuses) {
		$i18n = Localization::getTranslator();
		$badStatuses = [
			'FULL' => [
				'badWhen' => true,
				'body' => $i18n->_(
					'Bill stacker is full and cassette requires emptying.'
				),
			],
			'CASSETTE_PRESENT' => [
				'badWhen' => false,
				'body' => $i18n->_(
					'Cassette has been removed.'
				),
			],
			'MAINTENANCE_NEEDED' => [
				'badWhen' => true,
				'body' => $i18n->_(
					'Bill stacker has signalled it requires maintenance.'
				),
			]
		];
		
		foreach ($badStatuses as $status => $details) {
			if (array_key_exists($status, $statuses)
			&& $statuses[$status] === $details['badWhen']) {
				JobManager::enqueue(
					$this->db,
					'MachineStatusEmail',
					['body' => $this->reportError($details['body'])]
				);
			}
		}
	}
	
	public function execute(array $matches, $url, $rest) {
		$this->purchase = Purchase::load(
			$this->config,
			$this->db,
			intval($matches['ticket'])
		);
		
		$this->start();
		
		$this->maxFiat = $this->getWalletBalance();
		
		$this->send($this->getTotals());
		
		$this->driver
			->attachObserver('billInserted', function ($desc) {
				$this->billInsertedHandler($desc['billIndex']);
			})
			->attachObserver('stateChanged', function ($desc) {
				$this->send([
					'event' => 'stateChanged',
					'state' => $desc
				]);
			})
			->attachObserver('stateChanged', function ($statuses) {
				$this->statusHandler($statuses);
			})
			->attachObserver('driverStopped', function () {
				$this->send([
					'event' => 'driverStopped',
				]);
			})
			->run();
		
		$this->end();
		return true;
	}
}

