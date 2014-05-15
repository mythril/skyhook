<?php

namespace Controllers\Admin;
use Controllers\Controller;
use Environment\Post;
use Container;
use Admin;
use JSON;

class RestartMachine implements Controller {
	public function execute(array $matches, $url, $rest) {
		$post = Container::dispense('Environment\Post');
		$password = $post['password'];
		
		$admin = new Admin();
		
		if ($admin->auth($password)) {
			file_put_contents(
				'command',
				JSON::encode(['command' => 'restart-machine'])
			);
			echo JSON::encode(['success' => true]);
			return true;
		}
		
		header('HTTP/1.1 401 Unauthorized');
		header('Location: /');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		return false;
	}
}

