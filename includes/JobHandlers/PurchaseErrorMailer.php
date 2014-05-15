<?php

namespace JobHandlers;
use BufferedTemplate;
use Admin;
use Purchase;
use Container;
use Swift_SmtpTransport;
use Swift_Mailer;
use BillLogCSV;
use Swift_Message;
use Swift_Attachment;

trait PurchaseErrorMailer {
	public function sendMail($subject, $templateName, $ticketId) {
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
		
		$body = new BufferedTemplate($templateName);
		$body = $body->getBuffer([
			'ticket' => Purchase::load($cfg, $db, $ticketId)
		]);
		
		$billLog = Container::dispense('BillLogCSV')->save($ticketId);
		
		$message = Swift_Message::newInstance($cfg->getMachineName() . ': ' . $subject)
			->setFrom($from)
			->setTo([$cfg->getEmailUsername() => $cfg->getMachineName()])
			->setBody(strip_tags($body))
			->addPart($body, 'text/html');
		
		$errLog = '/home/pi/phplog/purchase_errors.' . $ticketId . '.log';
		
		if (file_exists($errLog)) {
			$message->attach(Swift_Attachment::fromPath(
				$errLog
			));
		}
		
		if ($billLog) {
			$message->attach(Swift_Attachment::fromPath(
				$billLog
			));
		}
		$mailer->send($message);
		if ($billLog) {
			unlink($billLog);
		}
	}
}
