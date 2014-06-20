<?php

class DB extends PDO {
	public function __construct(DateTimeZone $tz) {
		parent::__construct(
			'mysql:dbname=skyhook;host=127.0.0.1',
			'skyhook'
		);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$seconds = $tz->getOffset(new DateTime("now", new DateTimeZone('UTC')));
		$hrs = round($seconds / 60 / 60);
		$mins = round((abs($seconds) - (abs($hrs) * 60 * 60)) / 60);
		$strmins = strval($mins);
		if ($seconds >= 0) {
			$hrs = '+' . $hrs;
		}
		if (strlen($strmins) < 2) {
			$strmins = '0' . $strmins;
		}
		$this->exec("SET time_zone = '{$hrs}:{$strmins}';");
	}
}


