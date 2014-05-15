<?php

class ConfigVerifier {
	public function getErrors(Config $cfg) {
		$i18n = Localization::getTranslator();
		$pricingSettings = [];
		$walletSettings = [];
		$emailSettings = [];
		
		try {
			$price = $cfg->getPricingProvider()->getPrice();
			if (!is_numeric($price->get())) {
				$pricingSettings[] = [
					'id' => '#sources-methods-error',
					'error' => $i18n->_('Unknown format encountered:') . ' "' . $price . '".',
				];
			}
		} catch (Exception $e) {
			$pricingSettings[] = [
				'id' => '#sources-methods-error',
				'error' => $e->getMessage(),
			];
		}
		
		try {
			$balance = $cfg->getWalletProvider()->getBalance();
			if (!is_numeric($balance->get())) {
				$walletSettings[] = [
					'id' => '#wallet-id-error',
					'error' => $i18n->_('Unknown format encountered when attempmting to retreive balance:'). ' "' . $balance . '".',
				];
			}
		} catch (Exception $e) {
			$walletSettings[] = [
				'id' => '#wallet-id-error',
				'error' => $e->getMessage(),
			];
		}
		
		try {
			$t = new Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl');
			$t->setUsername($cfg->getEmailUsername())
				->setPassword($cfg->getEmailPassword())
				->start();
		} catch (Exception $e) {
			$emailSettings[] = [
				'id' => '#email-username-error',
				'error' => $e->getMessage(),
			];
		}
		
		$errors = [];
		
		if (!empty($pricingSettings)) {
			$errors['#pricing-settings'] = $pricingSettings;
		}
		
		if (!empty($walletSettings)) {
			$errors['#wallet-settings'] = $walletSettings;
		}
		
		if (!empty($emailSettings)) {
			$errors['#email-settings'] = $emailSettings;
		}
		
		return $errors;
	}
}
