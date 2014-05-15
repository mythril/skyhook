<?php

namespace CLIControllers;
use Controllers\Controller;
use Environment\Arguments;
use Container;
use Config;
use Currency;

class InstallCurrency implements Controller {
	public function execute(array $matches, $rest, $url) {
		$argv = Container::dispense('Environment\Arguments');
		$cfg = Container::dispense('Config');
		$code = strtoupper(trim(@$argv[2]));
		
		if (!Currency::validateCode($code)) {
			echo '"', $code, '" does not appear to be a valid currency code.', "\n";
			return true;
		}
		
		file_put_contents('currency', $code);
		$provPath = 'includes/PricingProviders';
		@unlink('/tmp_disk/price.json');
		@unlink($provPath);
		symlink(
			'Pricing/Providers/' . $cfg->getCurrencyCode(),
			$provPath
		);
	}
}
