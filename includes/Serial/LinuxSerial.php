<?php

namespace Serial;
use Exception;
use InvalidArgumentException;

class LinuxSerial implements Serial {
	private $device;
	private $rate;
	private $parity;
	private $byteSize;
	private $stopBits;
	private $handle;
	
	private function cmd($parameterized, $args) {
		if (!is_array($args)) {
			$args = [$args];
		}
		$cmd = vsprintf($parameterized, array_map('escapeshellarg', $args));
		$descriptors = [
			1 => ["pipe", "w"],
			2 => ["pipe", "w"],
		];
		
		$pipes = [];
		
		$proc = proc_open($cmd, $descriptors, $pipes);
		
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		
		$exitStatus = proc_close($proc);
		
		@fclose($pipes[1]);
		@fclose($pipes[2]);
		
		return [
			'stderr' => $stderr,
			'stdout' => $stdout,
			'exitStatus' => $exitStatus,
		];
	}
	
	public function __construct($device) {
		register_shutdown_function(function () {
			if ($this->isOpen()) {
				$this->close();
			}
		});
		$this->setDevice($device);
		$result = $this->cmd('stty -F %s -icanon min 0 time 0', $device);
		if ($result['exitStatus'] != 0) {
			throw new Exception('-icanon could not be set.');
		}
		foreach ([
			'raw',
			'-icrnl',
			'-opost',
			'-isig',
			'-iexten',
			'-echo',
			'-echoe',
			'-echok',
			'-echoctl',
			'-echoke',
		] as $option) {
			$result = $this->cmd('stty -F %s %s', [$device, $option]);
			if ($result['exitStatus'] != 0) {
				throw new Exception($option . ' could not be set.');
			}
		}
	}
	
	private function deviceExists($device) {
		return $this->cmd('stty -F %s', $device)['exitStatus'] == 0;
	}
	
	private function setDevice($device) {
		if (!$this->deviceExists($device)) {
			throw new Exception('Device not known, maybe permissions?');
		}
		$this->device = $device;
		return $this;
	}

	public function getDevice() {
		return $this->device;
	}
	
	private function isValidBaudRate($rate) {
		$rate = intval($rate);
		$rates = [
			110,
			150,
			300,
			600,
			1200,
			2400,
			4800,
			9600,
			19200,
			38400,
			57600,
			115200,
			230400,
			460800,
			500000,
			576000,
			921600,
			1000000,
			1152000,
			1500000,
			2000000,
			2500000,
			3000000,
			3500000,
			4000000,
		];
		return array_search($rate, $rates, true) !== false;
	}
	
	public function setBaudRate($rate) {
		if ($this->isValidBaudRate($rate)) {
			$result = $this->cmd(
				'stty -F %s %s',
				[
					$this->device,
					$rate,
				]
			);
			if ($result['exitStatus'] != 0) {
				throw new Exception('Baud rate could not be set: ' . $result['stderr']);
			}
			$this->rate = $rate;
			return $this;
		}
		throw new Exception('Baud rate could not be set: invalid rate.');
	}

	public function getBaudRate() {
		return $this->rate;
	}

	public function setParity($parity) {
		switch ($parity) {
		case self::PARITY_NONE:
			$parOpts = ['-parenb'];
			break;
		case self::PARITY_ODD:
			$parOpts = ['parenb', 'parodd'];
			break;
		case self::PARITY_EVEN:
			$parOpts = ['parenb', '-parodd'];
			break;
		default:
			throw new Exception('Invalid parity value: ' . $parity);
		};
		
		$result = $this->cmd('stty -F %s ' . implode(' ', $parOpts), $this->device);
		
		if ($result['exitStatus'] != 0) {
			throw new Exception('Unable to set Parity: ' . $result['stderr']);
		}
		
		$this->parity = $parity;
		
		return $this;
	}

	public function getParity() {
		return $this->parity;
	}

	public function setByteSize($size) {
		if ($size < 5) {
			throw new InvalidArgumentException('Byte size must be at least 5 bits.');
		}
		if ($size > 8) {
			throw new InvalidArgumentException('Byte size must be at most 8 bits.');
		}
		$this->byteSize = $size;
		$result = $this->cmd('stty -F %s cs%s', [$this->device, $this->byteSize]);
		
		if ($result['exitStatus'] != 0) {
			throw new Exception('Unable to set byte size: ' . $result['stderr']);
		}
		
		return $this;
	}

	public function getByteSize() {
		return $this->byteSize;
	}

	public function setStopBits($length) {
		$length = intval($length);
		if ($length === 1 || $length === 2) {
			$cstopb = ($length === 1 ? '-' : '') . 'cstopb';
			
			$result = $this->cmd('stty -F %s %s', [$this->device, $cstopb]);
			
			if ($result['exitStatus'] != 0) {
				throw new Exception('Error setting stop bits: ' . $result['stderr']);
			}
			
			$this->stopBits = $length;
			return $this;
		}
		throw new InvalidArgumentException('Stop bits must be either 1 or 2.');
	}

	public function getStopBits() {
		return $this->stopBits;
	}

	public function open($mode = "r+b") {
		if ($this->isOpen()) {
			throw new Exception('Device already open.');
		}
		
		if (!preg_match("@^[raw]\+?b?$@", $mode)) {
			throw new InvalidArgumentException('Mode must be compatible with fopen().');
		}
		
		$this->handle = fopen($this->device, $mode);
		
		if ($this->handle !== false) {
			$this->unblock();
		} else {
			throw new Exception('Unable to open device: ' . $this->device);
		}
		
		return $this;
	}
	
	public function close() {
		if (!$this->isOpen()) {
			throw new Exception('Device already closed.');
		}
		
		if (!fclose($this->handle)) {
			throw new Exception('Unable to close device: ' . $this->device);
		}
		
		unset($this->handle);
		return $this;
	}

	private function block() {
		stream_set_blocking($this->handle, 1);
	}

	private function unblock() {
		stream_set_blocking($this->handle, 0);
	}

	public function readLine() {
		$buffer = [];
		$this->block();
		while (true) {
			$c = $this->readBytes(1)[0];
			if ($c === ord("\r") || $c === ord("\n")) {
				break;
			}
			$buffer[] = $c;
		}
		$this->unblock();
		return $buffer;
	}

	public function readAvailable() {
		$buffer = [];
		$this->block();
		while ($this->hasDataAvailable()) {
			$byte = $this->readBytes(1)[0];
			$buffer[] = $byte;
		}
		$this->unblock();
		return $buffer;
	}

	public function readBytes($number = 128) {
		if ($number < 1) {
			throw new InvalidArgumentException('Number of bytes must be a positive non-zero integer.');
		}
		$buffer = [];
		while ($number > 0) {
			$char = fread($this->handle, 1);
			
			if ($char !== false) {
				$buffer[] = ord($char);
				$number -= 1;
			}
		}
		return $buffer;
	}

	public function write($str) {
		$len = strlen($str);
		$written = 0;
		
		while ($written < $len) {
			$remaining = substr($str, $written, $len - $written);
			$res = fwrite($this->handle, $remaining);
			if ($res === false) {
				throw new Exception('Device could not be written to.');
			}
			$written += $res;
		}
		
		return $this;
	}
	
	public function writeBytes(array $bytes) {
		$buffer = '';
		
		foreach ($bytes as $byte) {
			$buffer .= pack('C', $byte);
		}
		
		return $this->write($buffer);
	}

	public function hasDataAvailable() {
		$read = [$this->handle];
		$write = [];
		$except = [];

		return !!stream_select($read, $write, $except, 0);
	}

	public function isOpen() {
		return isset($this->handle);
	}
}
