<?php

namespace JobHandlers;
use JobHandler;
use BufferedTemplate;
use Admin;
use Purchase;
use Container;
use Swift_SmtpTransport;
use Swift_Mailer;
use BillLogCSV;
use Swift_Message;
use Swift_Attachment;

class EmailReceipt implements JobHandler {
	public function work(array $row) {
		$ticketId = intval($row['purchase_id']);
		$cfg = Admin::volatileLoad()->getConfig();
		$db = Container::dispense('DB');
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
		
		$body = new BufferedTemplate('emails/receipt');
		$body = $body->getBuffer([
			'machine' => $cfg->getMachineName(),
			'config' => $cfg,
			'ticket' => Purchase::load($cfg, $db, $ticketId)
		]);
		
		$subject = 'Transaction Summary';
		
		$message = Swift_Message::newInstance($cfg->getMachineName() . ': ' . $subject)
			->setFrom($from)
			->setTo([$row['email']])
			->setBody(strip_tags($body))
			->addPart($body, 'text/html');
			
		$mailer->send($message);
	}
}
