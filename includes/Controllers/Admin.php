<?php

namespace Controllers;
use Container;
use Router;

class Admin implements Controller {
	public function execute(array $matches, $url, $rest) {
		$ns = 'Controllers\\Admin\\';
		$router = Container::dispense('Router');
		$result = $router->resolve(
			$rest,
			[
				['/minimum-balance(\?.*)?$', Router::lazyLoad($ns . 'MinimumBalance')],
				['/saved$', Router::lazyLoad($ns . 'Saved')],
				['/email(\?.*)?$', Router::lazyLoad($ns . 'Email')],
				['/setup$', Router::lazyLoad($ns . 'Setup')],
				['/choose$', Router::lazyLoad($ns . 'Choose')],
				['/login(\?.*)?$', Router::lazyLoad($ns . 'Login')],
				['/boot(\?.*)?$', Router::lazyLoad($ns . 'Boot')],
				['/auth$', Router::lazyLoad($ns . 'Auth')],
				['/config$', Router::lazyLoad($ns . 'Config')],
				['/save$', Router::lazyLoad($ns . 'Save')],
				['/send-transaction-csv$', Router::lazyLoad($ns . 'SendTransactionCSV')],
				['/send-full-transaction-csv$', Router::lazyLoad($ns . 'SendFullTransactionCSV')],
				['/restart-machine$', Router::lazyLoad($ns . 'RestartMachine')],
				['/restart-services$', Router::lazyLoad($ns . 'RestartServices')],
			]
		);
		return $result['result'];
	}
}

