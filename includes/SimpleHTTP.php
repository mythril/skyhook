<?php

class SimpleHTTP {
	public static function get($url, $timeout = 45) {
		$curl = curl_init($url);
		curl_setopt_array(
			$curl,
			[
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_MAXREDIRS => 5,
				CURLOPT_FAILONERROR => false,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_TIMEOUT => intval($timeout),
				CURLOPT_CONNECTTIMEOUT => intval($timeout),
			]
		);
		
		$response = curl_exec($curl);
		
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		if ($status >= 400) {
			if (strlen($response) > 0) {
				throw new Exception($response, $status);
			} else {
				throw new Exception("Unknown cURL error encountered", $status);
			}
		}
		
		if (curl_errno($curl) !== 0) {
			curl_close($curl);
			throw new Exception("cURL Error: " . curl_error($curl));
		}
		return $response;
	}
}
