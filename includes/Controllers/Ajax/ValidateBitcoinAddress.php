<?php

namespace Controllers\Ajax;
use Controllers\Controller;
use AddressUtility;
use JSON;

class ValidateBitcoinAddress implements Controller {
	public function execute(array $matches, $url, $rest) {
		$addr = parse_url($matches['address'], PHP_URL_PATH);
		$isBitcoin = AddressUtility::checkAddress($addr);
		$isP2SH = AddressUtility::checkAddress(
			$addr,
			AddressUtility::P2SH_ADDRESS_VERSION
		);
		echo JSON::encode([
			'valid' => ($isBitcoin || $isP2SH)
		]);
		return true;
	}
}

