<?

class CSVMailer {
	public function send($lastOverride = false) {
		$i18n = Localization::getTranslator();
		$lastFn = '/home/pi/phplog/last-tx-sent';
		
		$last = 0;
		
		if (file_exists($lastFn)) {
			$last = intval(trim(file_get_contents($lastFn)));
		}
		
		if ($lastOverride !== false) {
			$last = $lastOverride;
		}
		
		$csvMaker = Container::dispense('TransactionCSV');
		$config = Admin::volatileLoad()->getConfig();
		
		$transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
			->setUsername($config->getEmailUsername())
			->setPassword($config->getEmailPassword());

		$msg = Swift_Message::newInstance()
			->setSubject($config->getMachineName() . $i18n->_(': Transaction Log'))
			->setFrom([$config->getEmailUsername() => $config->getMachineName()])
			->setTo(array($config->getEmailUsername()))
			->setBody($i18n->_('See attached for transaction log.'))
		;

		$file = $csvMaker->save($last);
		if (!$file) {
			throw new Exception('Unable to save CSV');
		}
		$msg->attach(Swift_Attachment::fromPath($file));
		
		file_put_contents($lastFn, $csvMaker->getLastID());

		$mailer = Swift_Mailer::newInstance($transport);

		if (!$mailer->send($msg)) {
			throw new Exception('Unable to send: unkown cause');
		}
	}
}
