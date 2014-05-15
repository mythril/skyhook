<?php

namespace Controllers\Ajax;
use Controllers\Controller;
use AddressUtility;
use JSON;

class ValidateBitcoinAddress implements Controller {
	public function execute(array $matches, $url, $rest) {
		echo JSON::encode([
			'valid' => AddressUtility::checkAddress(
				parse_url($matches['address'], PHP_URL_PATH)
			)
		]);
		return true;
	}
}

