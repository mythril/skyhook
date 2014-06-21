<?php

namespace Controllers;

class CacheBust implements Controller {
	public function execute(array $matches, $url, $rest) {
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache('user');
			apc_clear_cache();
			header('Refresh: 1;url=/start');
			echo "busted, redirecting";
			return true;
		}
	}
}

