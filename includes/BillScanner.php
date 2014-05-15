<?php

class BillScanner {
	private static $workingDir;
	private static $pidFile;
	private static $billLog;
	private static $receipt;
	private static $denominations;
	
	private $pid;
	
	public static function _static_init() {
		self::$denominations = Admin::volatileLoad()
			->getConfig()
			->getCurrencyMeta()
			->getDenominations();
		self::$workingDir  = realpath(__DIR__ . '/../scanner/');
		self::$pidFile = self::$workingDir . '/driver.pid';
		self::$billLog = self::$workingDir . '/input/bill_log.txt';
		self::$receipt = self::$workingDir . '/input/receipt';
		if (!file_exists(self::$workingDir . '/input')) {
			mkdir(self::$workingDir . '/input', 0770);
		}
	}
	
	private function getPid() {
		if (!isset($this->pid)) {
			if (!file_exists(self::$pidFile)) {
				return false;
			}
			$this->pid = intval(trim(file_get_contents(self::$pidFile)));
		}
		return $this->pid;
	}
	
	public function isRunning() {
		$pid = $this->getPid();
		if ($pid === false) {
			return false;
		}
		return !intval(
			trim(exec(
				'kill -0 ' . escapeshellarg($pid) . ' > /dev/null ; echo $?'
			))
		);
	}
	
	public function start() {
		if ($this->isRunning()) {
			throw new Exception("Bill scanner was already running.");
		}
		$dir = escapeshellarg(self::$workingDir);
		
		$this->pid = trim(exec(
			'cd ' . $dir . '; ./run_scanner_driver.py > /dev/null  & echo $!'
		));
		
		file_put_contents(self::$workingDir . '/driver.pid', $this->pid);
	}
	
	public function stop() {
		// Save a receipts file to the scanner directory so that the scanner
		// stops
		touch(self::$receipt);

		$end = microtime(true) + 2;
		
		$killed = false;
		
		// The scanner is stopped by placing a receipts file in the 'input'
		// directory, thus acknowledging the transaction is complete
		// so we wait a couple of seconds to see if it cleans up after itself
		while (microtime(true) < $end) {
			// 1/10th of a second
			usleep(100000);
			if ($this->isRunning() === false) {
				$killed = true;
				break;
			}
		}
		
		$pid = $this->getPid();
		
		// kill it manually
		if ($killed === false && $pid !== false) {
			exec('kill -9 ' . escapeshellarg($pid));
		}
		
		$files = array(
			'Receipt' => self::$receipt,
			'Bill Log' => self::$billLog,
			'Process ID (PID)' => self::$pidFile,
		);
		
		foreach($files as $desc => $file) {
			@unlink($file);
			clearstatcache(true, $file);
			if (file_exists($file)) {
				throw new Exception($desc . ' file could not be unlinked (' . $file . ').');
			}
		}
		
	}

	public function getBalance() {
		$balance = new Amount("0");
		
		// read scanner log into an array
		if (file_exists(self::$billLog)) {
			$amounts = file(self::$billLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			// loop through and add up the value of each line
			foreach ($amounts as $bill) {
				$balance = $balance->add(self::$denominations[$bill]);
			}
		}
		// Return the value to the caller
		return $balance;
	}
	
	public function getBillArray() {
		// returns an array that contains the bills insterted for this transaction
		$bills = array();
		
		if (file_exists(self::$billLog)) {
			$lines = file(self::$billLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			foreach ($lines as $bill) {
				if (isset($bills[$bill])) {
					$bills[$bill] += 1;
				} else {
					$bills[$bill] = 1;
				}
			}
		} else {
			if ($this->isRunning()) {
				throw new Exception('Bill scanner has not accepted any bills yet.');
			} else {
				throw new Exception('Bill scanner is not running, and there is no bill log.');
			}
		}
		
		return $bills;
	}
	
	public static function test() {
		declare(ticks = 1);
		$bs = new BillScanner();
		$bs->start();
		$handler = function () use($bs) {
			$bs->stop();
			exit();
		};
		pcntl_signal(SIGTERM, $handler);
		pcntl_signal(SIGINT, $handler);
		
		require_once "Amount.php";
		echo "\n";
		while (true) {
			$balance = $bs->getBalance();
			echo "Balance: ", $balance, "\n";
			if ($balance->isGreaterThan(new Amount("10"))) {
				break;
			}
			echo date('r'), "\n";
			sleep(1);
		}
		
		echo date('r'), "\n";
		var_dump($bs->getBillArray());
		echo date('r'), "\n";
		$bs->stop();
		echo date('r'), "\n";
	}
}

BillScanner::_static_init();

//BillScanner::test();
