<?php

namespace Controllers;

trait BalanceCacheUpdater {
	public function notifyBalanceChange() {
		$cli = realpath('./cli');
		exec("{$cli} address-monitor > /dev/null 2>&1 &");
	}
}
