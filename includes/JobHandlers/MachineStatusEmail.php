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

class MachineStatusEmail implements JobHandler {
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
		
		$subject = $row['subject'];
		
		$message = Swift_Message::newInstance($cfg->getMachineName() . ': ' . $subject)
			->setFrom($from)
			->setTo($cfg->getEmailUsername())
			->setBody($row['body']);
			
		$mailer->send($message);
	}
}
