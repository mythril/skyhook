<?php

namespace CLIControllers;
use Controllers\Controller;
use CSVMailer;
use HeartBeatMailer;
use Exceptions\TransactionCSVException;
use Hourly;
use JobManager;
use Container;
use Admin as AdminConfig;

class DoJobs implements Controller {
	public function execute(array $matches, $rest, $url) {
		// keep working for up to 4 minutes
		$until = time() + (60 * 4);
		Hourly::doWork([
			function () {
				$config = AdminConfig::volatileLoad()->getConfig();
				if (!$config->shouldSendLog()) {
					return;
				}
				try {
					$mailer = new CSVMailer();
					$mailer->send();
				} catch (TransactionCSVException $e) {
					$mailer = new HeartBeatMailer();
					$mailer->send();
				}
			},
		]);
		$manager = Container::dispense('JobManager');
		$manager->doWork($until);
	}
}
