<?php

namespace Controllers;
use JobManager;
use Template;
use Container;
use Purchase;
use Admin as AdminConfig;
use Environment\Post;

class EmailReceipt implements Controller {
	public function execute(array $matches, $url, $rest) {
		$post = Container::dispense('Environment\Post');
		$cfg = AdminConfig::volatileLoad()->getConfig();
		$db = Container::dispense('DB');
		
		if (filter_var($post['email'], FILTER_VALIDATE_EMAIL) !== $post['email']) {
			header('HTTP/1.1 303 See Other');
			header('Location: /receipt/' . intval($post['ticket']) . '?error=true');
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			return true;
		}
		
		$p = Purchase::load(
			$cfg,
			$db,
			intval($post['ticket'])
		);
		
		JobManager::enqueue(
			$db,
			'EmailReceipt',
			[
				'purchase_id' => $p->getId(),
				'email' => $post['email'],
			]
		);
		
		header('HTTP/1.1 303 See Other');
		header('Location: /receipt/' . intval($post['ticket']) . '?sent=true');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		return true;
	}
}

