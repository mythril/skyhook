<?php

class Config {
	private $data;
	
	private $walletProvider;
	private $pricingProvider;
	
	public function __construct() {
		$this->data = [
			'walletProvider' => [],
			'pricingProvider' => [],
			'email' => [],
		];
	}
	
	public function setData(array $data) {
		$this->data = $data;
		$this->walletProvider = ServiceLocater::resolve(
			$this->data['walletProvider'],
			'WalletProvider'
		);
		$this->pricingProvider = ServiceLocater::resolve(
			$this->data['pricingProvider'],
			'PricingProvider'
		);
	}
	
	private static $curCode;
	
	public function getCurrencyCode() {
		if (isset(self::$curCode)) {
			return self::$curCode;
		}
		if (file_exists('currency')) {
			$code = strtoupper(trim(file_get_contents('currency')));
			if (Currency::validateCode($code)) {
				self::$curCode = $code;
				return self::$curCode;
			}
			throw new UnexpectedValueException();
		}
		self::$curCode = 'USD';
		return self::$curCode;
	}
	
	public function getCurrencyMeta() {
		return Currency::getCurrencyMeta($this->getCurrencyCode());
	}
	
	public function getWalletProvider() {
		return $this->walletProvider;
	}
	
	public function getMaxTransactionValue() {
		if (isset($this->data['transactions']['maximum'])) {
			return new Amount($this->data['transactions']['maximum']);
		}
		return new Amount("0");
	}
	
	public function getContactInformation() {
		if (isset($this->data['contact']['information'])) {
			return $this->data['contact']['information'];
		}
		return '';
	}
	
	/**
	 * @return null|PricingProvider
	 */
	public function getPricingProvider() {
		return $this->pricingProvider;
	}
	
	public function getEmailPassword() {
		return $this->data['email']['password'];
	}
	
	public function getEmailUsername() {
		return $this->data['email']['username'];
	}
	
	public function getMachineName() {
		if (isset($this->data['email']['machine'])) {
			return $this->data['email']['machine'];
		}
		return 'Project Skyhook 00';
	}
	
	public function shouldSendLog() {
		if (isset($this->data['transaction-cron'])) {
			return !!$this->data['transaction-cron'];
		}
		return false;
	}
	
	public function isCurrencySupported($code) {
		throw new UnimplementedException;
	}
	
	public function asArray() {
		return $this->data;
	}
	
	public function isValid() {
		if (!isset(
			$this->pricingProvider,
			$this->walletProvider
		)) {
			return false;
		}
		return (
			$this->walletProvider->isConfigured()
			&& Currency::validateCode($this->getCurrencyCode())
			&& $this->pricingProvider->isConfigured()
			&& isset($this->data['email']['password'], $this->data['email']['username'])
		);
	}
	
	private $providerCache = [];
	
	public static function walkPricingProviders(PricingProvider $root, callable $visitor) {
		$visitor($root);
		
		if ($root instanceof PricingProxy) {
			foreach ($root->getDelegators() as $d) {
				self::walkPricingProviders($d, $visitor);
			}
		}
	}
	
	public function getPricingProviderByName($name) {
		if (empty($this->providerCache)) {
			$prov = $this->getPricingProvider();
			if ($prov) {
				self::walkPricingProviders($prov, function ($p) {
					$this->providerCache[get_class($p)] = $p;
				});
			}
		}
		if (isset($this->providerCache[$name])) {
			return $this->providerCache[$name];
		}
		return false;
	}
	
	private static function getFileMap($pattern, $ns) {
		return array_fill_keys(
			array_map(
				function ($a) use ($ns) {
					$result = explode('.', basename($a));
					array_pop($result);
					return $ns . "\\" . implode('.', $result);
				},
				glob($pattern)
			),
		true);
	}
	
	public static function getAvailablePricingProviders() {
		return self::getFileMap(
			__DIR__ . '/PricingProviders/*.php',
			'PricingProviders'
		);
	}
	
	public static function getAvailablePricingModifiers() {
		return self::getFileMap(
			__DIR__ . '/Pricing/Modifiers/*.php',
			'Pricing\Modifiers'
		);
	}
	
	public static function getAvailablePricingSelectors() {
		return self::getFileMap(
			__DIR__ . '/Pricing/Selectors/*.php',
			'Pricing\Selectors'
		);
	}
}



