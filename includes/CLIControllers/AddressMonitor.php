<?php

namespace CLIControllers;
use Controllers\Controller;
use Container;
use Config;
use Admin as AdminConfig;
use BitcoinAddress;
use Environment\Arguments;
use Exception;
use WalletProviders\BlockchainAddressMonitor;
use Debug;
use STDOUTLogger;

class AddressMonitor implements Controller {
	public function execute(array $matches, $rest, $url) {
		Debug::setLogger(new STDOUTLogger());
		$argv = Container::dispense('Environment\Arguments');
		if (isset($argv[2])) {
			$addr = new BitcoinAddress($argv[2]);
		} else {
			$addr = AdminConfig::volatileLoad()
				->getConfig()
				->getWalletProvider()
				->getWalletAddress();
		}
		$am = new BlockchainAddressMonitor($addr);
		$am->updateCache();
		echo $am->getBalance()->get(), "\n";
	}
}
