<?php

namespace CLIControllers;
use Controllers\Controller;
use JSON;
use Container;
use Router;

class HandleCommand implements Controller {
	public function execute(array $matches, $rest, $url) {
		$fp = fopen('/tmp/commandhandler.lock', 'r+');
		
		if (flock($fp, LOCK_EX | LOCK_NB)) {
			while (true) {
				$cmd = JSON::decode(file_get_contents('command'));
				$router = Container::dispense("Router");

				$result = $router->resolve(
					$cmd['command'],
					[
						['restart-machine$', function () {
							shell_exec('reboot && rm -f command');
						}],
						['restart-services$', function () {
							shell_exec('service apache2 restart && rm -f command');
						}],
					]
				);
				
				flock($fp, LOCK_UN);
				sleep(1);
			}
		}
		
		fclose($fp);
	}
}
