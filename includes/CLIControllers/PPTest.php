<?php

namespace CLIControllers;
use Controllers\Controller;
use Environment\Arguments;
use Container;
use Config;
use Currency;

class PPTest implements Controller {
	public function masterTest() {
		foreach (glob("includes/Pricing/Providers/*/", GLOB_ONLYDIR) as $code) {
			$code = basename($code);
			echo shell_exec(__DIR__ . "/../../cli pptest {$code}");
			//sleep to avoid pissing off provider
			sleep(1);
		}
	}
	
	public function execute(array $matches, $rest, $url) {
		$argv = Container::dispense('Environment\Arguments');
		$cfg = Container::dispense('Config');
		$code = strtoupper(trim(@$argv[2]));
		
		if (empty($code)) {
			$this->masterTest();
			return;
		}
		
		if (!Currency::validateCode($code)) {
			fputcsv(STDOUT, [$code, 'invalid code', 'failure']);
			return true;
		}
		
		$dir = realpath(__DIR__ . '/../Pricing/Providers/');
		
		$pps = [];
		
		foreach (glob("{$dir}/{$code}/*.php") as $rqpp) {
			require_once $rqpp;
			$bn = basename($rqpp, '.php');
			$className = "\\PricingProviders\\{$bn}";
			$pps[] = new $className();
		}
		
		foreach ($pps as $pp) {
			$provName = explode("\\", get_class($pp))[1];
			try {
				$pp->getPrice();
				fputcsv(STDOUT, [$code, $provName, 'success']);
			} catch (\Exception $e) {
				fputcsv(STDOUT, [$code, $provName, 'failure']);
			}
		}
	}
}
