<?php

class Debug {
	private static $logger;
	public static function setLogger($logger) {
		self::$logger = $logger;
	}
	public static function log($format) {
		if (isset(self::$logger)) {
			self::$logger->log($format);
		}
	}
}
