<?

class HeartBeatMailer {
	public function send() {
		$i18n = Localization::getTranslator();
		$config = Admin::volatileLoad()->getConfig();
		
		$transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
			->setUsername($config->getEmailUsername())
			->setPassword($config->getEmailPassword());

		$msg = Swift_Message::newInstance()
			->setSubject($config->getMachineName() . $i18n->_(': Transaction Log'))
			->setFrom([$config->getEmailUsername() => $config->getMachineName()])
			->setTo(array($config->getEmailUsername()))
			->setBody($i18n->_('There have been no transactions since the last email.'))
		;

		$mailer = Swift_Mailer::newInstance($transport);

		if (!$mailer->send($msg)) {
			throw new Exception('Unable to send: unkown cause');
		}
	}
}
