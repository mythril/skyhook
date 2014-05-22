<?php

namespace Controllers;
use BillScannerDriver;

trait ScannerStopper {
	public function stopScanner() {
		$scanner = new BillScannerDriver();
		$scanner->stop();
	}
}

