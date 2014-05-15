<?php

namespace Controllers\Ajax;
use Controllers\Controller;
use Container;
use JobManager;
use Admin;
use Config;
use DB;
use Purchase;
use UnexpectedValueException;
use JSON;

class AddEmailToTicket implements Controller {
	public function execute(array $matches, $url, $rest) {
		$get = Container::dispense('Environment\Get');
		$admin = Admin::volatileLoad();
		$cfg = $admin->getConfig();
		$db = Container::dispense('DB');
		
		$ticket = Purchase::load(
			$cfg,
			$db,
			intval($matches['ticket'])
		);
		
		try {
			$ticket->setEmailToNotify($get['email']);
		} catch (UnexpectedValueException $e) {
			echo JSON::encode([
				'invalid' => true
			]);
			return true;
		}
		
		Purchase::save(
			$db,
			$ticket
		);
		
		JobManager::enqueue(
			$db,
			'PurchaseErrorEmailProvided',
			['purchase_id' => $ticket->getId()]
		);
		
		echo JSON::encode(['success' => true]);
		return true;
	}
}
