<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Template;
use Container;
use Environment\Get;

class Boot implements Controller {
	public function execute(array $matches, $url, $rest) {
		$get = Container::dispense('Environment\Get');
		$error = 0;
		if (isset($get['error'])) {
			$error = (int)$get['error'];
		}
		$tmpl = new Template('admin/login');
		$tmpl->render([
			'errorCode' => $error,
			'submitTo' => '/admin/auth'
		]);
		return true;
	}
}

