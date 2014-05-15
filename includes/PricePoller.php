<?php

class PricePoller implements Iterator {
	private $pricingProvider;
	private $cacheTime = 0;
	private $current;
	private $currencyMeta;
	
	private static $cache = '/tmp_disk/price.json';
	
	public function __construct(PricingProvider $p) {
		$cfg = new Config();
		$this->currencyMeta = $cfg->getCurrencyMeta();
		$this->pricingProvider = $p;
		$this->loadCache();
	}
	
	public function current() {
		if (!isset($this->current)) {
			$this->next();
		}
		return $this->current;
	}
	
	public function key() {
		return 0;
	}
	
	private function loadCache() {
		if (!file_exists(self::$cache)) {
			return;
		}
		$cached = JSON::decode(file_get_contents(self::$cache));
		$this->current = $cached['price'];
		$this->cacheTime = intval($cached['time']);
	}
	
	private function cacheExpired() {
		return ($this->cacheTime + 45) < time();
	}
	
	private function refreshCache() {
		$cache = [];
		try {
			$this->current = $this->currencyMeta->format(
				$this->pricingProvider->getPrice(),
				false
			);
		} catch (Exception $e) {
			$this->current = "error";
			$cache['error'] = true;
			error_log($e);
		}
		$cache['price'] = strval($this->current);
		$this->cacheTime = time();
		$cache['time'] = $this->cacheTime;
		file_put_contents(self::$cache, JSON::encode($cache));
	}
	
	public function next() {
		if ($this->cacheExpired()) {
			$this->refreshCache();
			return;
		}
		$this->loadCache();
	}
	
	public function rewind() {
		//doesn't do anything
	}
	
	public function valid() {
		return true;
	}
}
