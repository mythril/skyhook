<?php

namespace Controllers;
use BillScanner;
use JSON;

class ScannerStopper implements Controller {
	public function execute(array $matches, $url, $rest) {
		$scanner = new BillScanner();
		if ($scanner->isRunning()) {
			$scanner->stop();
		}
		echo JSON::encode([
			'success' => true
		]);
	}
}
