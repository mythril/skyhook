<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Environment\Post;
use Template;
use Container;
use Admin;
use Config as CFG;
use ConfigNormalizer;

class Setup implements Controller {
	public function execute(array $matches, $url, $rest) {
		$tmpl = new Template('admin/config');
		$normalizer = new ConfigNormalizer();
		
		$cfg = new CFG();
		
		if (Admin::needsSetup()) {
			$tmpl->render([
				'currency' => $cfg->getCurrencyCode(),
				'config' => $normalizer->denormalize(new CFG()),
				'password' => '',
			]);
			return true;
		}
		
		header('Location: /admin/login?error=1');
		header('HTTP/1.1 401 Unauthorized');
		return false;
	}
}

