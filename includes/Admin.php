<?php

use Exceptions\InvalidPasswordException;
use Exceptions\SetupMachineException;
use Exceptions\AuthNeededException;

class Admin {
	
	private $password = false;
	private $config;
	private static $CONFIG_DECRYPTED = '/tmp_disk/config.json';
	private static $CONFIG;
	
	public static function _init() {
		if (isset(self::$CONFIG)) {
			return;
		}
		self::$CONFIG = 'enc.json';
	}
	
	public function auth($password) {
		if (!$this->configExists()) {
			return false;
		}
		try {
			$this->loadConfig($password);
		} catch (InvalidPasswordException $e) {
			return false;
		}
		if (isset($this->config)) {
			file_put_contents(
				self::$CONFIG_DECRYPTED,
				JSON::encode($this->config->asArray())
			);
			return true;
		}
		return false;
	}
	
	public function configExists() {
		return file_exists(self::$CONFIG);
	}
	
	public function isInitialSetupInProgress() {
		return !$this->configExists();
	}
	
	public function loadConfig($password) {
		$ciphertext = file_get_contents(self::$CONFIG);
		$plain = JSON::decode(Encryption::decrypt($password, $ciphertext));
		if ($plain['loaded'] === 'yes') {
			$this->config = new Config();
			$this->config->setData($plain);
		} else {
			throw new InvalidPasswordException("Password was not correct.");
		}
	}
	
	public function getConfig() {
		if (!isset($this->config)) {
			throw new BadMethodCallException('Config has not been loaded.');
		}
		return $this->config;
	}
	
	public function saveConfig($password, Config $cfg) {
		if ($cfg->isValid()) {
			$asArray = $cfg->asArray();
			$asArray['loaded'] = 'yes';
			$json = JSON::encode($asArray);
			$ciphertext = Encryption::encrypt(
				$password,
				$json
			);
			file_put_contents(self::$CONFIG_DECRYPTED, $json);
			file_put_contents(self::$CONFIG, $ciphertext);
			if (file_exists('/tmp_disk/price.json')) {
				unlink('/tmp_disk/price.json');
			}
		} else {
			throw new SetupMachineException("Configuration was invalid or incomplete");
		}
	}
	
	public static function volatileLoad() {
		$fn = self::$CONFIG_DECRYPTED;
		if (!file_exists(self::$CONFIG)) {
			throw new SetupMachineException();
		}
		if (!file_exists(self::$CONFIG_DECRYPTED)) {
			throw new AuthNeededException();
		}
		$admin = new Admin();
		$cfg = new Config();
		$cfg->setData(JSON::decode(file_get_contents($fn)));
		$admin->config = $cfg;
		return $admin;
	}
	
	public static function needsAuth() {
		try {
			self::volatileLoad();
			return false;
		} catch (InvalidPasswordException $e) {
			return true;
		} catch (SetupMachineException $e) {
			return true;
		} catch (AuthNeededException $e) {
			return true;
		}
	}
	
	public static function needsSetup() {
		try {
			self::volatileLoad();
			return false;
		} catch (InvalidPasswordException $e) {
			return false;
		} catch (SetupMachineException $e) {
			return true;
		} catch (AuthNeededException $e) {
			return false;
		}
	}
}

Admin::_init();
