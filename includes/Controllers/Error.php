<?php

namespace Controllers;
use Template;
use Container;

class Error implements Controller {
	public function execute(array $matches, $url, $rest) {
		$get = Container::dispense('Environment\Get');
		$symbols = [
			'ticket' => intval($matches['ticket']),
		];
		if (!empty($get['insufficient'])) {
			$symbols['nothanks'] = '/admin/minimum-balance';
		}
		$tmpl = new Template('error');
		$tmpl->render($symbols);
		return true;
	}
}

