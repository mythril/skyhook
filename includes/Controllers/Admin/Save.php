<?php

namespace Controllers\Admin;
use Exception;
use Controllers\Controller;
use Environment\Post;
use Container;
use Admin;
use JSON;
use ConfigNormalizer;
use ConfigValidator;
use ConfigVerifier;
use Localization;

class Save implements Controller {
	
	private function requireNewPassword(Post $post) {
		$errors = ['#password-settings' => []];
		$passwordSettings = &$errors['#password-settings'];
		if (strlen(@$post['admin_password']) < 5) {
			$passwordSettings[] = [
				'id' => '#password-error',
				'error' => 'Minimum password length is 5 characters.',
			];
		}
		if (@$post['admin_password'] === 'vending') {
			$passwordSettings[] = [
				'id' => '#password-error',
				'error' => 'A new password is required to secure this machine.',
			];
		}
		if (empty($passwordSettings)) {
			return [];
		}
		return $errors;
	}
	
	private static function addErrors($errors, $more) {
		foreach ($more as $k => $v) {
			if (isset($errors[$k])) {
				$errors[$k] = array_merge($errors[$k], $v);
			} else {
				$errors[$k] = $v;
			}
		}
		return $errors;
	}
	
	public function execute(array $matches, $url, $rest) {
		//a load from volatile storage should never happen here.
		$post = Container::dispense('Environment\Post');
		$admin = Container::dispense('Admin');
		$normalized = Container::dispense('ConfigNormalizer')->normalize($post);
		$errors = [];
		
		if ($admin->isInitialSetupInProgress()) {
			$errors = $this->addErrors(
				$errors,
				$this->requireNewPassword($post)
			);
		} else if (!$admin->auth($post['old_password'])) {
			error_log('Tampering detected, ' . time());
			return false;
		}
		
		$errors = $this->addErrors(
			$errors,
			Container::dispense('ConfigValidator')->getErrors($post)
		);
		
		if (count($errors) > 0) {
			echo JSON::encode(['errors' => $errors]);
			return true;
		}
		
		try {
			$cfg = Container::dispense('Config');
			$cfg->setData($normalized);
			if (!$cfg->isValid()) {
				throw new Exception('Uknown configuration error.');
			}
		} catch (Exception $e) {
			$errors = $this->addErrors(
				$errors,
				['#other-errors' => [[
					'id' => '#unknown-errors',
					'error' => $e->getMessage()
				]]]
			);
			error_log($e);
		}
		
		if (count($errors) > 0) {
			echo JSON::encode(['errors' => $errors]);
			return true;
		}
		
		$errors = $this->addErrors(
			$errors,
			Container::dispense('ConfigVerifier')->getErrors($cfg)
		);
		
		if (count($errors) > 0) {
			echo JSON::encode(['errors' => $errors]);
			return true;
		}
		
		try{
			$admin->saveConfig($post['admin_password'], $cfg);
			Localization::saveLocale($post['locale']);
		} catch (Exception $e) {
			$errors = $this->addErrors(
				$errors,
				['#other-errors' => [[
					'id' => '#unknown-errors',
					'error' => $e->getMessage()
				]]]
			);
			error_log($e);
		}
		
		if (count($errors) > 0) {
			echo JSON::encode(['errors' => $errors]);
		} else {
			echo JSON::encode(['saved' => true]);
		}
		return true;
	}
}

