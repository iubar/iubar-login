<?php

namespace Iubar\Login\Services;

class ApiKeyUtils {
	
	private static function generate(){
		$api_key = null;
		$random = null;
		if (!function_exists('openssl_cipher_iv_length') || !function_exists('openssl_random_pseudo_bytes') || !function_exists('openssl_encrypt')) {
			throw new Exception("Encryption function don't exists");
		} else {
			$random = openssl_random_pseudo_bytes(32);
			$api_key = sha1($random); // SHA1 produces a 40 character string
			// With a good hash function, the unpredictability of any part of the hash is proportional to the part's size.
			// If you want, you can encode it base 32 instead of the standard hex base 16.
			// Bear in mind that this will not significantly improve entropy per character (only by 25%).
			// For non-cryptographic uses, it does not matter whether you truncate MD5, SHA1 or SHA2.
			// Neither has any glaring deficiencies in entropy.
			$api_key = substr($api_key, 0, 25);
		}
			
		return $api_key;
	}
	
	public static function create(){
		$api_key = self::generate();
		if (!\Iubar\Login\Services\User::isApiKeyAvailable($api_key)){
			$api_key = self::generate();
		}
		
		return $api_key;
	}
	
	public static function formatApiKey($api_key){
		$pretty_api_key = null;
		$len = strlen($api_key);
		for($i = 0; $i < $len; $i++) {
			$char = substr($api_key, $i, 1);
			// $char contains the current character, so do your processing here
			$pretty_api_key = $pretty_api_key . $char;
			if($i % 5 == 4){
				$pretty_api_key = $pretty_api_key . "-";
			}
		}
		
		return $pretty_api_key;
	}
	
}