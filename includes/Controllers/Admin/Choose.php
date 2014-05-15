<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Template;

class Choose implements Controller {
	public function execute(array $matches, $url, $rest) {
		$tmpl = new Template('admin/choose');
		$tmpl->render([]);
		return true;
	}
}

