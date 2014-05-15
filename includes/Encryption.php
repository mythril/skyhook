<?php

require_once __DIR__ . "/../vendor/autoload.php";

class Encryption {
	const CYPHER = MCRYPT_RIJNDAEL_256;
	const MODE = MCRYPT_MODE_CBC;
	const ITERATIONS = 500;
	
	public static function encrypt($password, $secret) {
		$td = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
		$keySize = mcrypt_enc_get_key_size($td);
		$salt = openssl_random_pseudo_bytes($keySize);
		$key = hash_pbkdf2('sha256', $password, $salt, self::ITERATIONS, $keySize, true);
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$crypttext = mcrypt_generic($td, gzdeflate($secret));
		mcrypt_generic_deinit($td);
		return base64_encode($salt.$iv.$crypttext);
	}
	
	public static function decrypt($password, $crypttext) {
		$crypttext = base64_decode($crypttext);
		$plaintext = '';
		$td = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
		$saltSize = mcrypt_enc_get_key_size($td);
		$ivsize = mcrypt_enc_get_iv_size($td);
		$salt = substr($crypttext, 0, $saltSize);
		$iv = substr($crypttext, $saltSize, $ivsize);
		$crypttext = substr($crypttext, ($saltSize + $ivsize));
		$key = hash_pbkdf2('sha256', $password, $salt, self::ITERATIONS, $saltSize, true);
		if ($iv) {
			mcrypt_generic_init($td, $key, $iv);
			$zipped = mdecrypt_generic($td, $crypttext);
			try {
				$plaintext = gzinflate($zipped);
			} catch (ErrorException $e) {
				return "";
			}
		}
		return rtrim($plaintext, "\0");
	}
	
	public static function test1() {
		foreach (array(
			'123321' => 'abccba ',
			'12332' => ' abccba ',
			'7' => ' abccba !',
		) as $key => $secret) {
			$mangled = self::decrypt($key, self::encrypt($key, $secret));
			
			if ($mangled !== $secret) {
				throw new Exception("Encryption test failure.");
			} else {
				echo "Pass\n";
			}
		}
	}
}

//Encryption::test1();


