<?php

/**
 * This uses a numerically stable representation for money values.
 */
class Amount {
	const SCALE = 8;
	
	private $amt;
	
	public function __construct($amt) {
		if (!is_numeric($amt)) {
			throw new UnexpectedValueException("Amount was constructed with: $amt");
		}
		if (is_object($amt) && get_class($amt) !== 'Amount') {
			throw new UnexpectedValueException("Amount was constructed with: $amt");
		}
		$this->amt = strval($amt);
	}
	
	public static function fromSatoshis($satoshis) {
		if (!is_numeric($satoshis)) {
			throw new UnexpectedValueException("Amount was constructed with: $satoshis satoshis");
		}
		$s = bcdiv($satoshis, '100000000', 8);
		return new Amount($s);
	}
	
	public function get() {
		return $this->amt;
	}
	
	public function toSatoshis() {
		$s = bcmul($this->amt, '100000000');
		return new Amount(bcmul($s, '1', 0));
	}
	
	public function add(Amount $b) {
		return new Amount(bcadd($this->get(), $b->get(), Amount::SCALE));
	}
	
	public function subtract(Amount $b) {
		return new Amount(bcsub($this->get(), $b->get(), Amount::SCALE));
	}
	
	public function multiplyBy(Amount $b) {
		return new Amount(bcmul($this->get(), $b->get(), Amount::SCALE));
	}

	public function divideBy(Amount $b) {
		return new Amount(bcdiv($this->get(), $b->get(), Amount::SCALE));
	}
	
	public function modulusBy(Amount $b) {
		return new Amount(bcmod($this->get(), $b->get(), Amount::SCALE));
	}
	
	public function truncate() {
		$asStr = strval($this->amt);
		$truncated = substr(
			$asStr,
			0,
			intval(strpos($asStr, '.'))
		);
		return new Amount($truncated);
	}
	
	public function isEqualTo(Amount $other) {
		return self::compare($this, $other) === 0;
	}
	
	public function isGreaterThan(Amount $other) {
		return self::compare($this, $other) === 1;
	}
	
	public function isLessThan(Amount $other) {
		return self::compare($this, $other) === -1;
	}
	
	private static function compare(Amount $a, Amount $b) {
		return bccomp($a, $b, Amount::SCALE);
	}
	
	public function __toString() {
		return $this->amt;
	}
}


