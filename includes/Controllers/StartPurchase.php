<?php

namespace Controllers;
use Container;
use Config;
use DB;
use Admin as AdminConfig;
use BitcoinAddress;
use Purchase;
use JobHandlers\MachineStatusEmail;
use Localization;
use Math;

class StartPurchase implements Controller {
	public function execute(array $matches, $url, $rest) {
		$addr = new BitcoinAddress($matches['address']);
		$admin = AdminConfig::volatileLoad();
		$cfg = $admin->getConfig();
		$db = Container::dispense('DB');
		
		$ticket = Purchase::create(
			$cfg,
			$db,
			$addr
		);
		
		$wallet = $cfg->getWalletProvider();
		
		$i18n = Localization::getTranslator();
		$balance = $wallet->getBalance()->multiplyBy($ticket->getBitcoinPrice());
		
		$threshhold = Math::max([
			$cfg->getMaxTransactionValue(),
			Math::max($cfg->getCurrencyMeta()->getDenominations())
		]);
		
		if ($balance->isLessThan($threshhold)) {
			MachineStatusEmail::reportError(
				$cfg,
				$db,
				$i18n->_('Low balance: ') . $balance->get() . ' ' . $i18n->_('bitcoin')
			);
		}
		
		header('HTTP/1.1 303 See Other');
		header('Location: /purchase/' . $addr->get() . '/' . $ticket->getId());
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		return true;
	}
}

