<?php

/**
 * Simple wrapper to catch error conditions
 */
class JSON {
	public static function encode(
		$value,
		$options = 0
	) {
		return json_encode($value, $options);
	}
	
	private static function preserveBigInts($json) {
		return preg_replace('/:\s*(\d{14,})/', ': "${1}"', $json);
	}
	
	public static function decode(
		$json,
		$assoc = true,
		$depth = 512,
		$options = JSON_BIGINT_AS_STRING
	) {
		if ($options & JSON_BIGINT_AS_STRING) {
			$json = self::preserveBigInts($json);
		}
		$decoded = json_decode($json, $assoc, $depth, $options);
		$lastError = json_last_error();
		if ($lastError == JSON_ERROR_NONE) {
			return $decoded;
		}
		
		$msgs = [];
		
		foreach ([
			JSON_ERROR_DEPTH => "The maximum stack depth has been exceeded",
			JSON_ERROR_STATE_MISMATCH => "Invalid or malformed JSON",
			JSON_ERROR_CTRL_CHAR => "Control character error, possibly incorrectly encoded",
			JSON_ERROR_SYNTAX => "Syntax error",
			JSON_ERROR_UTF8 => "Malformed UTF-8 characters, possibly incorrectly encoded",
		] as $condition => $message) {
			if ($lastError & $condition) {
				$msgs[] = "JSON Error: " . $message . " (error code: " . $condition . ")";
			}
		}
		
		throw new Exception(implode("\n", $msgs));
	}
}


