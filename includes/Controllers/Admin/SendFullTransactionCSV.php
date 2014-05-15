<?php

namespace Controllers\Admin;
use Controllers\Controller;
use JSON;
use CSVMailer;
use Exception;

class SendFullTransactionCSV implements Controller {
	public function execute(array $matches, $url, $rest) {
		try{
			$mailer = new CSVMailer();
			$mailer->send(0);
		} catch (Exception $e) {
			echo JSON::encode([
				'error' => $e->getMessage()
			]);
			return true;
		}
		
		echo JSON::encode([
			'sent' => true
		]);

		return true;
	}
}

