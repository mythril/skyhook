<?php

class DB extends PDO {
	public function __construct() {
		parent::__construct(
			'mysql:dbname=skyhook;host=127.0.0.1',
			'skyhook'
		);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}


