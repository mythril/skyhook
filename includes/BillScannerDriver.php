<?php

use Serial\Serial;
use Serial\LinuxSerial;

class BillScannerDriver implements Observable {
	use Observed;
	
	private $acceptableBills = 0x7F;
	private $billRejected = false;
	private $shouldExit = false;
	private $lockFile;
	private static $QUIT_FN = '/tmp_disk/scanner_quit';
	
	private function nthBill($index) {
		return 1 << $index;
	}
	
	private function billsByte(array $ordinates) {
		$byte = 0;
		
		foreach($ordinates as $ordinate) {
			$byte |= $this->nthBill($ordinate - 1);
		}
		
		return $byte;
	}
	
	private function checkSum(array $bytes) {
		$sum = 0;
		
		foreach ($bytes as $byte) {
			$sum ^= $byte;
		}
		
		return $sum;
	}
	
	private function sendMessage(Serial $ser, array $bytes) {
		$sum = $this->checkSum(array_slice($bytes, 1, -1));
		
		$bytes[] = $sum;
		
		$ser->writeBytes($bytes);
		return $this;
	}
	
	private function isResponseValid(array $bytes) {
		$sum = $this->checkSum(array_slice($bytes, 1, -2));
		return $sum === $bytes[count($bytes) - 1];
	}
	
	private function binout($bytes) {
		$result = '';
		for ($i = 0; $i < count($bytes); $i += 1) {
			$result .= str_pad(sprintf('%x', $bytes[$i]), 2, '0', STR_PAD_LEFT);
		}
		return $result;
	}
	
	public function setAcceptableBills(array $ordinalBills) {
		$this->acceptableBills = $this->billsByte($ordinalBills);
		return $this;
	}
	
	public function disableBills(array $ordinalBills) {
		$this->acceptableBills = (~$this->billsByte($ordinalBills)) & 0x7f;
		return $this;
	}
	
	private function getStatus(array $bytes) {
		$statuses = [
			'IDLING',
			'ACCEPTING',
			'ESCROWED',
			'STACKING',
			'STACKED',
			'RETURNING',
			'RETURNED',
			'CHEATED',
			'REJECTED',
			'JAMMED',
			'FULL',
			'CASSETTE_PRESENT',
		];
		
		$result = [];
		
		foreach ($statuses as $i => $status) {
			$statusMask = 1 << ($i % 7);
			$byteIndex = floor($i / 7) + 3;
			
			$result[$status] = !!($bytes[$byteIndex] & $statusMask);
		}
		
		$secondStatuses = [
			'POWERING_UP',
			'INVALID_COMMAND',
			'MAINTENANCE_NEEDED',
		];
		
		foreach ($secondStatuses as $i => $status) {
			$statusMask = 1 << ($i % 7);
			$byteIndex = floor($i / 7) + 5;
			$result[$status] = !!($bytes[$byteIndex] & $statusMask);
		}
		
		return $result;
	}
	
	public function stop() {
		touch(self::$QUIT_FN);
		$this->shouldExit = true;
		return $this;
	}
	
	public function shouldExit() {
		if (file_exists(self::$QUIT_FN)) {
			unlink(self::$QUIT_FN);
			return true;
		}
		return $this->shouldExit;
	}
	
	public function setBillRejected($bool) {
		$this->billRejected = $bool;
		return $this;
	}
	
	public function isBillRejected() {
		return $this->billRejected;
	}
	
	private $lastTime;
	
	private function pause() {
		usleep(0.1 * 1000000);
	}
	
	public function isAlreadyLocked() {
		$this->lockFile = fopen('/tmp/driver.lock', 'w+');
		
		return !flock($this->lockFile, LOCK_EX | LOCK_NB);
	}
	
	private function lock() {
		if ($this->isAlreadyLocked()) {
			throw new Exception("Unable to acquire lock, locked elsewhere");
		}
		
		register_shutdown_function(function () {
			$this->unlock();
		});
		return $this;
	}
	
	private function unlock() {
		if (!isset($this->lockFile)) {
			return $this;
		}
		flock($this->lockFile, LOCK_UN);
		fclose($this->lockFile);
		unset($this->lockFile);
		return $this;
	}
	
	private function getSerialConnection() {
		$serial = new LinuxSerial('/dev/ttyUSB0');
		return $serial->setBaudRate(9600)
			->setParity(Serial::PARITY_EVEN)
			->setByteSize(7)
			->setStopBits(1)
			->open();
	}
	
	public function run() {
		if (file_exists(self::$QUIT_FN)) {
			@unlink(self::$QUIT_FN);
			sleep(1);
		}
		$this->lock();
		$this->shouldExit = false;
		$serial = $this->getSerialConnection(); 
		
		$ackBit = 0;
		$escrowed = false;
		$oldCred = false;
		$returnBill = false;
		$statusText = '';
		while (true) {
			$this->notifyObservers('tick', []);
			$msg = [0x02, 0x08, 0x10, $this->acceptableBills, 0x10, 0x00, 0x03];
			$msg[2] = 0x10 | $ackBit;
			$ackBit = ($ackBit === 0) ? 1 : 0;
			
			if ($escrowed && !$returnBill) {
				$msg[4] |= 0x20;
			}
			
			if ($escrowed && $returnBill) {
				$msg[4] |= 0x40;
				$returnBill = false;
			}
			
			Debug::log("Sending Message");
			$this->sendMessage($serial, $msg);
			$this->pause();
			
			Debug::log("Reading Response");
			$out = $serial->readAvailable();
			
			if (count($out) === 0) {
				continue;
			}
			
			if (!$this->isResponseValid($out)) {
				error_log('Invalid response: ' . $this->binout($out));
				$this->notifyObservers('invalidResponse', ['bytes' => $out]);
				continue;
			}
			
			Debug::log($this->binout($out));
			Debug::log("Extracting Status");
			$status = $this->getStatus($out);
			
			$tmp = implode(' ', array_keys(array_filter($status)));
			if ($statusText !== $tmp) {
				Debug::log('Notifying stateChanged');
				$this->notifyObservers('stateChanged', $status);
				$statusText = $tmp;
			}
			
			$escrowed = (ord($out[3]) & 4) ? true : false;
			
			$billIndex = ($out[5] & 0x38) >> 3;
			
			if ($billIndex !== $oldCred && $billIndex !== 0) {
				Debug::log("Notifying billInserted");
				$this->notifyObservers('billInserted', ['billIndex' => $billIndex]);
				$oldCred = $billIndex;
			}
			
			$returnBill = $this->isBillRejected();
			$this->setBillRejected(false);
			
			$this->pause();
			if ($this->shouldExit()) {
				$this->sendMessage(
					$serial,
					//disables acceptance of all bills, exits.
					[0x02, 0x08, 0x10, 0x00, 0x10, 0x00, 0x03]
				);
				$this->notifyObservers('driverStopped', []);
				break;
			}
		}
		$this->unlock();
		return $this;
	}
}
