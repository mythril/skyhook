<?php
namespace BitcoinTransactions;
use BitcoinTransaction;

class BlockchainTransaction implements BitcoinTransaction {
	private $txid;
	private $message;
	private $notice;
	
	public function __construct(array $blockchainResponse) {
		$this->txid = $blockchainResponse['tx_hash'];
		$this->message = @$blockchainResponse['message'];
		$this->notice = @$blockchainResponse['notice'];
	}
	
	public function getId() {
		return $this->txid;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	public function getNotice() {
		return $this->notice;
	}
}


