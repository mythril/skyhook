<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Environment\Post;
use Template;
use Container;
use Admin;
use Config as CFG;
use ConfigNormalizer;

class Config implements Controller {
	public function execute(array $matches, $url, $rest) {
		$post = Container::dispense('Environment\Post');
		$password = $post['password'];
		$normalizer = new ConfigNormalizer();
		
		$admin = new Admin();
		
		$tmpl = new Template('admin/config');
		
		$cfg = new CFG();
		
		if ($admin->auth($password)) {
			$tmpl->render([
				'currency' => $cfg->getCurrencyCode(),
				'password' => $password,
				'config' => $normalizer->denormalize($admin->getConfig()),
			]);
			return true;
		}
		
		header('HTTP/1.1 303 See Other');
		if (empty($post['alt'])) {
			header('Location: /admin/login?error=1');
		} else {
			if ($post['alt'] === 'email') {
				header('Location: /admin/email?error=1');
			} else {
				header('Location: /admin/minimum-balance?error=1');
			}
		}
		return false;
	}
}

