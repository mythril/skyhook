<?php

namespace CLIControllers;
use Controllers\Controller;
use JSON;
use Encryption;
use Environment\Arguments;
use Container;

class EncryptConfig implements Controller {
	public function execute(array $matches, $rest, $url) {
		$argv = Container::dispense('Environment\Arguments');
		$fn = trim(@$argv[2]);
		if (empty($fn)) {
			echo "Nothing to encrypt.\n";
			return;
		}
		if (!file_exists($fn)) {
			echo "File not found: ({$fn})\n";
			return;
		}
		try {
			$config = JSON::decode(file_get_contents($fn));
			$config['loaded'] = 'yes';
			$config = JSON::encode($config);
		} catch (\Exception $e) {
			echo "{$fn} does not appear to be a valid JSON file.\n";
			echo $e;
			return;
		}
		$prompt = 'Please enter the password to encrypt with';
		$password = substr(
			shell_exec(
				'/usr/bin/env bash -c \'read -s -p "' . $prompt . ':" pass && echo $pass\''
			),
			0,
			-1
		);
		echo "\n";
		$encrypted = Encryption::encrypt($password, $config);
		unset($password);
		file_put_contents('enc.json', $encrypted);
		echo "enc.json saved.\n";
	}
}
