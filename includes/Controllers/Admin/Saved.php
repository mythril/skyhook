<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Template;

class Saved implements Controller {
	public function execute(array $matches, $url, $rest) {
		$t = new Template('admin/saved');
		$t->render([]);
		header('Refresh: 5;url=/start');
		return false;
	}
}

