<?php

namespace JobHandlers;
use JobHandler;
use BufferedTemplate;
use Admin;
use Container;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;
use Localization;
use Config;
use DB;
use JobManager;

class MachineStatusEmail implements JobHandler {
	public static function reportError(Config $cfg, DB $db, $msg) {
		$i18n = Localization::getTranslator();
		$body = $i18n->_('This is an automated message.') . "\n\n"
			. sprintf($i18n->_('%s has encountered an error.'), $cfg->getMachineName()) . "\n\n"
			. $i18n->_('Time: ') . date('g:ia \o\n l jS F Y e') . "\n\n"
			. $i18n->_('Error Type: ' . $msg);
		JobManager::enqueue(
			$db,
			'MachineStatusEmail',
			['body' => $body]
		);
	}
	
	public function work(array $row) {
		$cfg = Admin::volatileLoad()->getConfig();
		$transport = Swift_SmtpTransport::newInstance(
				'smtp.gmail.com',
				465,
				'ssl'
			)
			->setUsername($cfg->getEmailUsername())
			->setPassword($cfg->getEmailPassword());
		$mailer = Swift_Mailer::newInstance($transport);
		
		$from = [];
		$from[$cfg->getEmailUsername()] = $cfg->getMachineName();
		$i18n = Localization::getTranslator();
		$message = Swift_Message::newInstance(
				$cfg->getMachineName() . ': ' . $i18n->_('Error Report.')
			)
			->setFrom($from)
			->setTo($cfg->getEmailUsername())
			->setBody($row['body']);
			
		$mailer->send($message);
	}
}
