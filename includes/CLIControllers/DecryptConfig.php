<?php

namespace CLIControllers;
use Controllers\Controller;
use JSON;
use Encryption;
use Environment\Arguments;
use Container;

class DecryptConfig implements Controller {
	public function execute(array $matches, $rest, $url) {
		$argv = Container::dispense('Environment\Arguments');
		$fn = trim(@$argv[2]);
		if (empty($fn)) {
			echo "Nothing to decrypt.\n";
			return;
		}
		if (!file_exists($fn)) {
			echo "File not found: ({$fn})\n";
			return;
		}
		$prompt = 'Please enter the password to decrypt with';
		$password = substr(
			shell_exec(
				'/usr/bin/env bash -c \'read -s -p "' . $prompt . ':" pass && echo $pass\''
			),
			0,
			-1
		);
		echo "\n";
		$decrypted = JSON::decode(
			Encryption::decrypt($password, file_get_contents($fn))
		);
		unset($password);
		echo "\n", JSON::encode($decrypted, JSON_PRETTY_PRINT), "\n";
	}
}
