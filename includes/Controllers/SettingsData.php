<?php

namespace Controllers;
use Admin as AdminConfig;
use JSON;
use Localization;

class SettingsData implements Controller {
	public function execute(array $matches, $url, $rest) {
		$cfg = AdminConfig::volatileLoad()->getConfig();
		$meta = $cfg->getCurrencyMeta();
		$denoms = [];
		
		foreach ($meta->getDenominations() as $denom) {
			$denoms[] = $denom->get();
		}
		
		header('Content-Type: application/javascript');
		echo 'var CurrencyData = ' . JSON::encode([
			'symbol' => $meta->getSymbol(),
			'contactInfo' => $cfg->getContactInformation(),
			'code' => $meta->getISOCode(),
			'denominations' => $denoms,
		]) . ';';
		echo 'var Languages = ' . JSON::encode(Localization::getAvailableLocales()) . ';';
		return true;
	}
}
