<?php

namespace Controllers;
use Admin as AdminConfig;
use JSON;

class CurrencyData implements Controller {
	public function execute(array $matches, $url, $rest) {
		$meta = AdminConfig::volatileLoad()->getConfig()->getCurrencyMeta();
		$denoms = [];
		
		foreach ($meta->getDenominations() as $denom) {
			$denoms[] = $denom->get();
		}
		
		header('Content-Type: application/javascript');
		echo 'var CurrencyData = ' . JSON::encode([
			'symbol' => $meta->getSymbol(),
			'code' => $meta->getISOCode(),
			'denominations' => $denoms,
		]) . ';';
		return true;
	}
}
