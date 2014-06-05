<?php

namespace Controllers\Ajax;
use Controllers\Controller;
use Container;
use Environment\Post;
use JSON;
use ConfigNormalizer;
use ConfigValidator;
use ConfigVerifier;
use Config;

class TestPrice implements Controller {
	public function execute(array $matches, $url, $rest) {
		$post = Container::dispense("Environment\\Post");
		$settings = ConfigNormalizer::normalizePricingSettings($post);
		$validator = Container::dispense('ConfigValidator');
		$validationErrors = $validator->getPricingErrors($post);
		$verifier = Container::dispense('ConfigVerifier');
		$verificationErrors = $verifier->getPricingErrors($settings);
		$errors = [
			'#pricing-settings' => array_merge(
				$validationErrors,
				$verificationErrors['errors']
			)
		];
		echo JSON::encode([
			'price' => $verificationErrors['price'],
			'errors' => $errors
		]);
		return true;
	}
}
