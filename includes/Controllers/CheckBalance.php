<?php

namespace Controllers;
use Admin as AdminConfig;
use Amount;
use JSON;
use Config;
use Container;

class CheckBalance implements Controller {
	use Comet;
	
	private function interval() {
		$SECOND = 1000000;
		return 60 * $SECOND;
	}
	
	public function execute(array $matches, $url, $rest) {
		$admin = AdminConfig::volatileLoad();
		$config = $admin->getConfig();
		$wallet = $config->getWalletProvider();
		
		$initialBalance = $wallet->getBalance();
		
		$interval = $this->interval();
		
		$this->start();
		
		while (true) {
			try {
				$balance = $wallet->getBalance();
				$this->send($balance->isGreaterThan(
					$initialBalance
				));
			} catch (\Exception $e) {
				error_log($e);
			}
			usleep($interval);
			if (connection_aborted()) {
				break;
			}
		}
		
		$this->end();
		return true;
	}
}

