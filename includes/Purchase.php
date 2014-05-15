<?php

class Purchase {
	const CANCELED = -2;
	const ERROR = -1;
	const PENDING = 0;
	const COMPLETE = 1;
	
	private function __construct() { }
	
	public static function normalizeTXID($txid) {
		return trim(SimpleHTTP::get(
			'https://blockchain.info/q/hashtontxid/' . $txid
		));
	}
	
	/**
	 * Starts a new purchase
	 *
	 * @param DB $db a connection to the database
	 * @param Amount $price required to start a new ticket.
	 */
	public static function create(
		Config $cfg,
		DB $db,
		BitcoinAddress $address
	) {
		$prepared = $db->prepare('
			INSERT INTO `purchases` (
				`customer_address`,
				`bitcoin_price`,
				`cur_code`
			) VALUES (
				:customer_address,
				:bitcoin_price,
				:cur_code
			)
		');
		
		$cur = $cfg->getCurrencyCode();
		
		$result = $prepared->execute(array(
			':customer_address' => $address->get(),
			':bitcoin_price' => $cfg->getPricingProvider()->getPrice()->get(),
			':cur_code' => $cur,
		));
		
		if ($result === false) {
			throw new Exception("Unable to create new purchase ticket.");
		}
		
		return self::load($cfg, $db, $db->lastInsertId());
	}
	
	public static function load(Config $cfg, DB $db, $id) {
		$stmt = $db->prepare('
			SELECT
				`id`,
				`initiated_at`,
				`customer_address`,
				`currency_amount`,
				`bitcoin_price`,
				`bitcoin_amount`,
				`txid`,
				`ntxid`,
				`status`,
				`cur_code`,
				`finalized_at`,
				`message`,
				`notice`,
				`email_to_notify`
			FROM `purchases`
			WHERE `id`=:id
		');
		
		$stmt->execute(array(
			':id' => $id
		));
		
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
		
		$purchase = new self();
		$purchase
			->setConfig($cfg)
			->setInitiatedAt(new DateTime($row['initiated_at']))
			->setCustomerAddress(new BitcoinAddress($row['customer_address']))
			->setCurrencyAmount(new Amount($row['currency_amount']))
			->setBitcoinPrice(new Amount($row['bitcoin_price']))
			->setBitcoinAmount(new Amount($row['bitcoin_amount']))
			->setCurrency($row['cur_code'])
			->setFinalizedAt(new DateTime($row['finalized_at']))
			->setTXID($row['txid'])
			->setNTXID($row['txid'])
			->setStatus($row['status'])
			->setMessage($row['message'])
			->setNotice($row['notice'])
			->setEmailToNotify($row['email_to_notify'])
			->setId($id);
		return $purchase;
	}
	
	public static function save(DB $db, Purchase $p) {
		$stmt = $db->prepare('
			UPDATE `purchases`
			SET `currency_amount` = :currency_amount,
				`bitcoin_amount` = :bitcoin_amount,
				`txid` = :txid,
				`ntxid` = :ntxid,
				`status` = :status,
				`message` = :message,
				`notice` = :notice,
				`email_to_notify` = :email_to_notify
			WHERE `id` = :id
		');
		
		$result = $stmt->execute(array(
			':id' => $p->getId(),
			':currency_amount' => $p->getCurrencyAmount(),
			':bitcoin_amount' => $p->getBitcoinAmount(),
			':txid' => $p->getTXID(),
			':ntxid' => $p->getNTXID(),
			':message' => $p->getMessage(),
			':notice' => $p->getNotice(),
			':status' => $p->getStatus(),
			':email_to_notify' => $p->getEmailToNotify()
		));
		
		return $result;
	}
	
	public static function finalize(DB $db, Purchase $p) {
		$stmt = $db->prepare('
			UPDATE `purchases`
			SET `finalized_at` = CURRENT_TIMESTAMP()
			WHERE `id` = :id
		');
		
		$result = $stmt->execute(array(
			':id' => $p->getId(),
		));
		
		return $result;
	}
	
	public static function completeTransaction(
		Config $cfg,
		DB $db,
		Purchase $p
	) {
		$w = $cfg->getWalletProvider();
		if ($p->isCompleted()) {
			throw new Exception("completeTransaction() was attempted twice on purchase ticket: " . $p->getId());
		}
		
		try {
			$db->beginTransaction();
			
			$tx = $w->sendTransaction(
				$p->getCustomerAddress(),
				$p->recalculateBitcoinAmount()
			);
			
			$p->setTXID($tx->getId())
				->setNTXID(self::normalizeTXID($tx->getId()))
				->setStatus(self::COMPLETE)
				->setMessage($tx->getMessage())
				->setNotice($tx->getNotice());
			
			self::finalize($db, $p);
			self::save($db, $p);
			
			$db->commit();
		} catch (Exception $e) {
			$db->rollback();
			$erroredOut = self::load($cfg, $db, $p->getId());
			$erroredOut->setStatus(self::ERROR);
			self::save($db, $erroredOut);
			throw $e;
		}
	}
	
	private $initiatedAt;
	
	private function setInitiatedAt(DateTime $dt) {
		$this->initiatedAt = $dt;
		return $this;
	}
	
	public function getInitiatedAt() {
		return $this->initiatedAt;
	}
	
	private $id;
	
	private function setId($id) {
		$this->id = $id;
		return $this;
	}
	
	public function getId() {
		return $this->id;
	}
	
	private $bitcoinPrice;
	
	private function setBitcoinPrice(Amount $bitcoinPrice) {
		$this->bitcoinPrice = $bitcoinPrice;
		return $this;
	}
	
	public function getBitcoinPrice() {
		return $this->bitcoinPrice;
	}
	
	private $addr;
	
	private function setCustomerAddress(BitcoinAddress $addr) {
		$this->addr = $addr;
		return $this;
	}
	
	public function getCustomerAddress() {
		return $this->addr;
	}
	
	private $currencyAmount;
	
	public function setCurrencyAmount(Amount $amt) {
		//TODO when bill tracking is added, make this private
		$this->currencyAmount = $amt;
		return $this;
	}
	
	public function addCurrencyAmount(Amount $amt) {
		$this->currencyAmount = $amt->add($this->currencyAmount);
		return $this;
	}
	
	public function getCurrencyAmount() {
		return $this->currencyAmount;
	}
	
	private $bitcoinAmount;
	
	public function setBitcoinAmount(Amount $amt) {
		$this->bitcoinAmount = $amt;
		return $this;
	}
	
	public function recalculateBitcoinAmount() {
		$this->setBitcoinAmount(
			$this->getCurrencyAmount()->divideBy($this->getBitcoinPrice())
		);
		return $this->bitcoinAmount;
	}
	
	public function getBitcoinAmount() {
		return $this->bitcoinAmount;
	}
	
	private $status;
	
	public function setStatus($status) {
		$this->status = intval($status);
		return $this;
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	private $txid;
	
	public function getTXID() {
		return $this->txid;
	}
	
	private function setTXID($txid) {
		$this->txid = $txid;
		return $this;
	}
	
	private $ntxid;
	
	public function getNTXID() {
		return $this->ntxid;
	}
	
	private function setNTXID($ntxid) {
		$this->ntxid = $ntxid;
		return $this;
	}
	
	public function isCompleted() {
		return $this->getStatus() === Purchase::COMPLETE
			&& $this->getTXID() !== null;
	}
	
	private $finalizedAt;
	
	private function setFinalizedAt(DateTime $dt) {
		$this->finalizedAt = $dt;
		return $this;
	}
	
	public function getFinalizedAt() {
		return $this->finalizedAt;
	}
	
	private $currency;
	
	private function setCurrency($c) {
		//$c = strtoupper($c);
		return $this;
		/**
		// TODO restore this functionality
		if ($this->getConfig()->isCurrencySupported($c)) {
			$this->currency = $c;
			return $this;
		}
		throw new UnexpectedValueException("Unsupported currency type: '$c'");
		*/
	}
	
	public function getCurrency() {
		return $this->currency;
	}
	
	private $config;
	
	private function setConfig(Config $cfg) {
		$this->config = $cfg;
		return $this;
	}
	
	private function getConfig() {
		return $this->config;
	}
	
	private $message;
	
	private function setMessage($msg) {
		$this->message = $msg;
		return $this;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	private $notice;
	
	private function setNotice($msg) {
		$this->notice = $msg;
		return $this;
	}
	
	public function getNotice() {
		return $this->notice;
	}
	
	private $emailToNotify;
	
	public function setEmailToNotify($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL) !== $email && $email !== null) {
			throw new UnexpectedValueException('Email address was invalid.');
		}
		$this->emailToNotify = $email;
		return $this;
	}
	
	public function getEmailToNotify() {
		return $this->emailToNotify;
	}
}





