<?php

namespace WalletProviders;
use BitcoinTransactions\BlockchainTransaction;
use BitcoinAddress;
use JSON;
use Exception;
use Exceptions\InsufficientFundsException;
use Amount;
use SimpleHTTP;

class Blockchain implements \WalletProvider {
	private $mainPass;
	private $secondPass;
	private $id;
	private $fromAddress;
	private static $URL = "https://blockchain.info/merchant/";
	
	private function baseURL() {
		return self::$URL . urlencode($this->id) . '/';
	}
	
	public function configure(array $options) {
		try {
			$this->fromAddress = new BitcoinAddress($options['fromAddress']);
		} catch(\InvalidArgumentException $e) {
			throw new \ConfigurationException($e->getMessage());
		}
		
		$this->mainPass = $options['mainPass'];
		$this->secondPass = $options['secondPass'];
		$this->id = $options['id'];
	}
	
	public function verifyOwnership() {
		$request = $this->baseURL() . 'address_balance?' . http_build_query([
			'password' => $this->mainPass,
			'address' => $this->fromAddress->get()
		]);
		
		try {
			$get = SimpleHTTP::get($request);
		} catch (Exception $e) {
			throw new Exception("There was a network error while processing the request.");
		}
		
		$decoded = JSON::decode($get);
		
		if (isset($decoded['error'])) {
			throw new Exception('Blockchain.info responded with: ' . $decoded['error']);
		}
		
		return true;
	}
	
	public function getBalance($confirmations = 1) {
		$request = 'https://blockchain.info/unspent?' . http_build_query([
			'active' => $this->fromAddress->get()
		]);
		
		try {
			$get = SimpleHTTP::get($request);
		} catch (Exception $e) {
			throw new Exception("There was a network error while processing the request.");
		}
		
		$decoded = JSON::decode($get);
		$balance = new Amount('0');
		
		foreach ($decoded['unspent_outputs'] as $output) {
			if (!empty($output['confirmations'])
			&& $output['confirmations'] >= $confirmations) {
				$balance = $balance->add(Amount::fromSatoshis(
					$output['value']
				));
			}
		}
		
		if (isset($decoded['error'])) {
			throw new Exception('Blockchain.info responded with: ' . $decoded['error']);
		}
		
		return $balance;
	}
	
	public function isConfigured() {
		return isset(
			$this->mainPass,
			$this->secondPass,
			$this->id,
			$this->fromAddress
		);
	}
	
	public function sendTransaction(BitcoinAddress $to, Amount $howMuch) {
		if ($this->getBalance()->isLessThan($howMuch)) {
			throw new InsufficientFundsException();
		}
		
		$request = $this->baseURL() . 'payment?' . http_build_query([
			'password' => $this->mainPass,
			'second_password' => $this->secondPass,
			'from' => $this->fromAddress->get(),
			'to' => $to->get(),
			'amount' => $howMuch->toSatoshis()->get()
		]);
		
		try {
			$encoded = SimpleHTTP::get($request);
		} catch (Exception $e) {
			throw new Exception("There was a network error while processing the request.");
		}
		
		$decoded = JSON::decode($encoded);
		error_log('blockchain: ' . $encoded . "\n");
		
		if (isset($decoded['error'])) {
			throw new Exception('Blockchain.info responded with: ' . $decoded['error']);
		}
		
		return new BlockchainTransaction($decoded);
	}
}



