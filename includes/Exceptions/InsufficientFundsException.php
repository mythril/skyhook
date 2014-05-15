<?php

namespace Exceptions;
use Exception;

class InsufficientFundsException extends Exception {
	public function __construct(
		$msg = 'Insufficient Bitcoin to satisfy request.',
		$code = 0,
		Exception $previous = null
	) {
		parent::__construct($msg, $code, $previous);
	}
}
