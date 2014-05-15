<?

use Environment\Post;

class ConfigValidator {
	public function getErrors(Post $post) {
		$i18n = Localization::getTranslator();
		if (isset($post['sources'])) {
			$sources = explode(',', $post['sources']);
		} else {
			$sources = [];
		}
		$pricingSettings = [];
		
		if (empty($post['selector'])) {
			$pricingSettings[] = [
				'id' => '#sources-methods-error',
				'error' => $i18n->_('A choice must be made between Single\Highest\Lowest'),
			];
		} else {
			if ($post['selector'] === 'single') {
				if (count($sources) !== 1) {
					$pricingSettings[] = [
						'id' => '#sources-methods-error',
						'error' => $i18n->_('If Single is chosen, exactly 1 pricing source must be chosen.'),
					];
				}
			} else {
				if (count($sources) < 1) {
					$pricingSettings[] = [
						'id' => '#sources-methods-error',
						'error' => $i18n->_('At least one pricing source must be chosen.'),
					];
				}
			}
		}
		
		$providers = Config::getAvailablePricingProviders();
		
		foreach ($sources as $source) {
			if (!isset($providers[$source])) {
				$pricingSettings[] = [
					'id' => '#sources-methods-error',
					'error' => $i18n->_('Unrecognized pricing source:') . ' "' . $source . '".',
				];
			}
		}
		
		if (array_search('PricingProviders\StaticPrice', $sources) !== false) {
			if (!isset($post['staticPrice'])) {
				$pricingSettings[] = [
					'id' => '#sources-methods-error',
					'error' => $i18n->_('When using static price a value is required.'),
				];
			} elseif (!is_numeric($post['staticPrice'])) {
				$pricingSettings[] = [
					'id' => '#sources-methods-error',
					'error' => $i18n->_('Static price value must be numeric.'),
				];
			}
		}
		
		$modifiers = Config::getAvailablePricingModifiers();
		
		if (isset($post['modifier']) && is_array($post['modifier'])) {
			foreach ($post['modifier'] as $modifier => $value) {
				if (!isset($modifiers[$modifier])) {
					$pricingSettings[] = [
						'id' => '#sources-methods-error',
						'error' => $i18n->_('Unrecognized pricing modifier:') . ' "' . $modifier . '".',
					];
				}
				if (!is_numeric($value)) {
					$pricingSettings[] = [
						'id' => '#sources-methods-error',
						'error' => $i18n->_('Value must be numeric for:') . ' "' . $modifier . '".',
					];
				}
			}
		}
		
		//TODO: implement range checking on modifier values, and static pricing
		
		$walletSettings = [];
		
		if (empty($post['wallet']['id'])) {
			$walletSettings[] = [
				'id' => '#wallet-id-error',
				'error' => $i18n->_('A valid Blockchain.info wallet id is required.'),
			];
		}
		
		if (empty($post['wallet']['mainPass'])) {
			$walletSettings[] = [
				'id' => '#wallet-mainPass-error',
				'error' => $i18n->_('Your respective Blockchain.info password is required.'),
			];
		}
		
		if (empty($post['wallet']['fromAddress'])) {
			$walletSettings[] = [
				'id' => '#wallet-fromAddress-error',
				'error' => $i18n->_('An address controlled by your Blockchain.info wallet is required to send from.'),
			];
		} elseif (!AddressUtility::checkAddress($post['wallet']['fromAddress'])) {
			$walletSettings[] = [
				'id' => '#wallet-fromAddress-error',
				'error' => $i18n->_('A valid Bitcoin address is required.'),
			];
		}
		
		$emailUser = @$post['email']['username'];
		$emailSettings = [];
		
		if (empty($emailUser)) {
			$emailSettings[] = [
				'id' => '#email-username-error',
				'error' => $i18n->_('A valid email address is required.'),
			];
		} elseif (filter_var($emailUser, FILTER_VALIDATE_EMAIL) !== $emailUser) {
			$emailSettings[] = [
				'id' => '#email-username-error',
				'error' => $i18n->_('Email address entered is not valid.'),
			];
		}
		
		if (empty($post['email']['password'])) {
			$emailSettings[] = [
				'id' => '#email-password-error',
				'error' => $i18n->_('Email password is required.'),
			];
		}
		
		$passwordSettings = [];
		
		if (strlen(@$post['admin_password']) < 5) {
			$passwordSettings[] = [
				'id' => '#password-error',
				'error' => $i18n->_('Minimum password length is 5 characters.'),
			];
		}
		
		if (@$post['admin_password'] !== @$post['confirm_admin_password']) {
			$passwordSettings[] = [
				'id' => '#password-error',
				'error' => $i18n->_('Admin passwords must match'),
			];
		}
		
		$transactionSettings = [];
		
		if (isset($post['transactions']['maximum'])) {
			if (!preg_match('#^[0-9]+$#', $post['transactions']['maximum'])) {
				$transactionSettings[] = [
					'id' => '#maximum-errors',
					'error' => $i18n->_('Maximum transaction value must be a positive integer.'),
				];
			}
		}
		
		$localeSettings = [];
		
		if (isset($post['locale'])) {
			if (!Localization::localePresent($post['locale'])) {
				$transactionSettings[] = [
					'id' => '#locale-errors',
					'error' => $i18n->_('Unkown Locale.'),
				];
			}
		}
		
		$errors = [];
		
		if (!empty($transactionSettings)) {
			$errors['#locale-settings'] = $localeSettings;
		}
		
		if (!empty($transactionSettings)) {
			$errors['#transaction-settings'] = $transactionSettings;
		}
		
		if (!empty($passwordSettings)) {
			$errors['#password-settings'] = $passwordSettings;
		}
		
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
