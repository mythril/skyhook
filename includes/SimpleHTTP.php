<?php

class SimpleHTTP {
	public static function get($url, $timeout = 45) {
		$context = stream_context_create(['http' => [
			//TODO update this if PHP fixes the bug.
			'timeout' => ($timeout / 2),
		]]);
		return file_get_contents($url, false, $context);
	}
}
