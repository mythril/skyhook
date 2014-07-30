<?php

namespace CLIControllers;
use Controllers\Controller;
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

class AddressMonitor implements Controller {
	private static $cacheDir = '/home/pi/phplog/balanceCalc/';
	private $addr;
	private $cacheFN;
	private $cached = false;
	private $txs;
	private $unspentLookup;
	
	public function __construct() {
		$argv = Container::dispense('Environment\Arguments');
		if (isset($argv[2])) {
			$this->addr = new BitcoinAddress($argv[2]);
		} else {
			$this->addr = AdminConfig::volatileLoad()
				->getConfig()
				->getWalletProvider()
				->getWalletAddress();
		}
		if (!file_exists(self::$cacheDir)) {
			mkdir(self::$cacheDir, 0700);
		}
		$this->cacheFN = self::$cacheDir . $this->addr->get() . '.json';
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
		if (flock($fp, LOCK_EX | LOCK_NB)) {
			fwrite($fp, $this->summarize());
			fflush($fp);
			flock($fp, LOCK_UN);
		} else {
			fclose($fp);
			throw new Exception("Unable to acquire lock.");
		}
		fclose($fp);
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
		$response = JSON::decode(HTTP::get(
			'http://blockchain.info/rawtx/' . urlencode($hash)
		));
		sleep(1);
		return $response;
	}
	
	private function buildCache() {
		$this->unspentLookup = [];
		$this->txs = [];
		$this->updateCache();
	}
	
	private function loadCache() {
		$fp = fopen($this->cacheFN, 'r');
		if (flock($fp, LOCK_SH | LOCK_NB)) {
			$this->desummarize(fread($fp, filesize($this->cacheFN)));
			flock($fp, LOCK_UN);
		} else {
			fclose($fp);
			throw new Exception("Unable to acquire lock.");
		}
		fclose($fp);
	}
	
	private function calculateBalance() {
		$addr = $this->addr->get();
		$balance = 0;
		foreach ($this->txs as $tx) {
			if ($tx['double_spend']) {
				continue;
			}
			foreach ($tx['inputs'] as $in) {
				if (count($in) < 1) {
					break;
				}
				if ($in['prev_out']['addr'] === $addr) {
					// continues outer loop also
					continue 2;
				}
			}
			foreach ($tx['out'] as $out) {
				if ($out['addr'] !== $addr) {
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
	}
	
	private function updateCache() {
		$newUnspent = $this->getUnspent();
		$this->txs = array_filter($this->txs, function ($tx) use (&$newUnspent) {
			return isset($newUnspent[$tx['hash']]);
		});
		
		$common = array_intersect_key($this->unspentLookup, $newUnspent);
		
		$toFetch = array_diff_key($newUnspent, $common);
		
		if (count($toFetch) < 1) {
			return;
		}
		
		$txs = [];
		foreach ($toFetch as $hash => $idx) {
			$txs[$hash] = $this->fetchTX($hash);
		}
		$this->txs = array_values($txs);
		
		$this->saveCache();
	}
	
	public function execute(array $matches, $rest, $url) {
		if (!$this->isCached()) {
			$this->buildCache();
		} else {
			$this->loadCache();
		}
		$this->updateCache();
		echo $this->calculateBalance()->get(), "\n";
	}
}
