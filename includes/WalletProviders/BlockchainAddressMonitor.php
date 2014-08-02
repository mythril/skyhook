<?php

namespace WalletProviders;
use Container;
use Config;
use Admin as AdminConfig;
use Debug;
use BitcoinAddress;
use STDOUTLogger;
use Environment\Arguments;
use Exception;
use SimpleHTTP as HTTP;
use JSON;
use Amount;

class BlockchainAddressMonitor {
	private static $cacheDir = '/home/pi/phplog/balanceCalc/';
	private $addr;
	private $cacheFN;
	private $cached = false;
	private $txs;
	private $unspentLookup;
	
	public function __construct(BitcoinAddress $addr, $blockingLoad = false) {
		$this->addr = $addr;
		$this->unspentLookup = [];
		$this->txs = [];
		if (!file_exists(self::$cacheDir)) {
			mkdir(self::$cacheDir, 0700);
		}
		$this->cacheFN = self::$cacheDir . $this->addr->get() . '.json';
		if (!is_writable($this->cacheFN)) {
			throw new Exception('Cache file is not writable');
		}
		if ($this->isCached()) {
			$this->loadCache($blockingLoad);
		}
	}
	
	private function isCached() {
		if ($this->cached) {
			return true;
		}
		$this->cached = file_exists($this->cacheFN);
		return $this->cached;
	}
	
	private function saveCache() {
		$fp = fopen($this->cacheFN, 'w+');
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $this->summarize());
			fflush($fp);
			flock($fp, LOCK_UN);
		} else {
			fclose($fp);
			throw new Exception("Unable to acquire lock.");
		}
		fclose($fp);
		return $this;
	}
	
	private function getUnspent() {
		$addr = $this->addr->get();
		
		$response = JSON::decode(HTTP::get(
			'http://blockchain.info/unspent?active=' . urlencode($addr)
		));
		
		$dedup = [];
		
		foreach ($response['unspent_outputs'] as $txo) {
			$hash = implode('', array_reverse(str_split($txo['tx_hash'], 2)));
			$dedup[$hash] = $txo['tx_index'];
		}
		
		return $dedup;
	}
	
	private function fetchTX($hash) {
		Debug::log('fetching: ' . $hash);
		$response = JSON::decode(HTTP::get(
			'http://blockchain.info/rawtx/' . urlencode($hash)
		));
		sleep(1);
		return $response;
	}
	
	private function loadCache($blocking = false) {
		$fp = fopen($this->cacheFN, 'r');
		$lockOpts = LOCK_SH;
		if (!$blocking) {
			$lockOpts |= LOCK_NB;
		}
		if (flock($fp, $lockOpts)) {
			$this->desummarize(fread($fp, filesize($this->cacheFN)));
			flock($fp, LOCK_UN);
		} else {
			fclose($fp);
			throw new Exception("Unable to acquire lock.");
		}
		Debug::log("tx count: " . count($this->txs));
		fclose($fp);
		return $this;
	}
	
	private function calculateBalance($confirmations = false) {
		$addr = $this->addr->get();
		$blockHeight = 0;
		if ($confirmations !== false) {
			$blockHeight = intval(HTTP::get(
				'https://blockchain.info/q/getblockcount'
			));
		}
		$balance = 0;
		foreach ($this->txs as $tx) {
			if ($tx['double_spend']) {
				Debug::log('Double spend: ' . $tx['tx_index']);
				continue;
			}
			foreach ($tx['inputs'] as $in) {
				if (isset($in['prev_out']['addr'])) {
					if ($in['prev_out']['addr'] === $addr) {
						// continues outer loop also
						Debug::log('Inputs from same address: ' . $tx['tx_index']);
						continue 2;
					} elseif ($confirmations !== false) {
						if (($tx['block_height'] + $confirmations) > $blockHeight) {
							continue 2;
						}
					}
				}
			}
			foreach ($tx['out'] as $out) {
				if ($out['addr'] !== $addr) {
					//Debug::log('Outputs are not for this address: ' . $tx['tx_index']);
					continue;
				}
				$balance += $out['value'];
			}
		}
		return Amount::fromSatoshis($balance);
	}
	
	private function summarize() {
		return JSON::encode($this->txs);
	}
	
	private function desummarize($data) {
		$this->txs = JSON::decode($data);
		foreach ($this->txs as $tx) {
			$this->unspentLookup[$tx['hash']] = $tx['tx_index'];
		}
		return $this;
	}
	
	public function updateCache() {
		$newUnspent = $this->getUnspent();
		$this->txs = array_filter($this->txs, function ($tx) use (&$newUnspent) {
			return isset($newUnspent[$tx['hash']]);
		});
		
		$common = array_intersect_key($this->unspentLookup, $newUnspent);
		
		$toFetch = array_diff_key($newUnspent, $common);
		
		if (count($toFetch) < 1) {
			return;
		}
		
		Debug::log("number of txs to fetch: " . count($toFetch)); 
		
		$txs = [];
		
		foreach ($this->txs as $tx) {
			if (isset($common[$tx['hash']])) {
				$txs[$tx['hash']] = $tx;
			}
		}
		
		foreach ($toFetch as $hash => $idx) {
			$txs[$hash] = $this->fetchTX($hash);
		}
		$this->txs = array_values($txs);
		
		$this->saveCache();
		return $this;
	}
	
	public function getBalance($confirmations = false) {
		return $this->calculateBalance($confirmations);
	}
	
	public function getCachedData() {
		return $this->txs;
	}
}
