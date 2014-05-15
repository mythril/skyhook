<?php

namespace Controllers;
use Admin as AdminConfig;
use Exception;
use Swift_SmtpTransport;

class ConfigChecker implements Controller {
	public function execute(array $matches, $url, $rest) {
		if (AdminConfig::needsSetup()) {
			header('HTTP/1.1 303 See Other');
			header('Location: /admin/setup');
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			return true;
		}
		
		if (AdminConfig::needsAuth()) {
			header('HTTP/1.1 303 See Other');
			header('Location: /admin/choose');
			header('Cache-Control: no-cache, no-store, must-revalidate');
			header('Pragma: no-cache');
			header('Expires: 0');
			return true;
		}
		
		if (!file_exists('/tmp_disk/email')) {
			$cfg = AdminConfig::volatileLoad()->getConfig();
			try {
				$t = new Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl');
				$t->setUsername($cfg->getEmailUsername())
					->setPassword($cfg->getEmailPassword())
					->start();
				
				if (strlen(trim($cfg->getEmailPassword())) < 1) {
					throw new Exception("no way");
				}
				if (strlen(trim($cfg->getEmailUsername())) < 1) {
					throw new Exception("no way");
				}
				file_put_contents('/tmp_disk/email', '');
			} catch (Exception $e) {
				header('HTTP/1.1 303 See Other');
				header('Location: /admin/email');
				header('Cache-Control: no-cache, no-store, must-revalidate');
				header('Pragma: no-cache');
				header('Expires: 0');
				return true;
			}
		}
		
		return false;
	}
}


