<?php

namespace CLIControllers;
use Controllers\Controller;
use Container;
use Config;
use BillScannerDriver;
use Debug;
use STDOUTLogger;

class ScannerDriver implements Controller {
	public function execute(array $matches, $rest, $url) {
		//Debug::setLogger(new STDOUTLogger());
		$cfg = new Config;
		$currencyMeta = $cfg->getCurrencyMeta();
		$scanner = new BillScannerDriver();
		$scanner->stop();
		foreach([
			SIGINT,
			SIGTERM,
		] as $signal) {
			pcntl_signal($signal, function () use ($scanner) {
				$scanner->stop();
			});
		}
		$oldState = [];
		$scanner
			->attachObserver('tick', function () {
				pcntl_signal_dispatch();
			})
			->attachObserver('billInserted', function ($desc) use ($currencyMeta, $scanner) {
				$denoms = $currencyMeta->getDenominations();
				if ($desc['billIndex'] === 4) {
					$scanner->setBillRejected(true);
					echo "-=[ REJECTING BILL FROM CLI-DRIVER ]=-\n";
				}
				echo 'Bill Inserted(' . $desc['billIndex'] . '): ', $currencyMeta->format($denoms[$desc['billIndex']]), ' (', $currencyMeta->getISOCode(), ")\n";
			})
			->attachObserver('stateChanged', function ($desc) use (&$oldState) {
				foreach ($desc as $state => $value) {
					if (isset($oldState[$state]) && $oldState[$state] !== $value) {
						echo $state, ": ", $value ? 'true' : 'false', "\n";
					}
					$oldState[$state] = $value;
				}
			})
			->attachObserver('driverStopped', function () {
				echo "Driver stopped\n";
			})
			->run();
	}
}
