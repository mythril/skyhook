<?php

class STDOUTLogger {
	public function log($format) {
		$args = array_slice(func_get_args(), 1);
		echo vsprintf($format, $args), "\n";
	}
}

