<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Environment\Post;
use Template;
use Container;
use Admin;

class Auth implements Controller {
	public function execute(array $matches, $url, $rest) {
		$post = Container::dispense('Environment\Post');
		$password = $post['password'];
		
		$admin = new Admin();
		
		if ($admin->auth($password)) {
			header('HTTP/1.1 303 See Other');
			header('Location: /start');
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			return true;
		}
		
		header('HTTP/1.1 401 Unauthorized');
		header('Location: /admin/boot?error=1');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		return false;
	}
}

