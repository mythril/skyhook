<?php

namespace Controllers;
use BillScanner;

trait ScannerStopper {
	public function stopScanner() {
		$scanner = new BillScanner();
		if ($scanner->isRunning()) {
			$scanner->stop();
		}
	}
}

