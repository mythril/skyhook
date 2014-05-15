<?php

namespace Exceptions;
use Exception;

class SetupMachineException extends Exception {
	public function __construct($message = 'Machine has not been set up.', $code = 0, Exception $prev = null) {
		parent::__construct($message, $code, $prev);
	}
}
