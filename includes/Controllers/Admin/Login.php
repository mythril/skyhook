<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Template;
use Container;
use Environment\Get;

class Login implements Controller {
	public function execute(array $matches, $url, $rest) {
		$get = Container::dispense('Environment\Get');
		$error = 0;
		if (isset($get['error'])) {
			$error = (int)$get['error'];
		}
		$symbols = [
			'errorCode' => $error,
		];
		if (isset($get['redirect'])) {
			$symbols['redirectOnTimeout'] = $get['redirect'];
		}
		$tmpl = new Template('admin/login');
		$tmpl->render($symbols);
		return true;
	}
}

