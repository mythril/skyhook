<?php

namespace Exceptions;
use Exception;

class AuthNeededException extends Exception {
	public function __construct($message = 'System requires authentication.', $code = 0, Exception $prev = null) {
		parent::__construct($message, $code, $prev);
	}
}
