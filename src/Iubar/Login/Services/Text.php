<?php

namespace Iubar\Login\Services;

class Text {
	
	private static $texts;
	
	// Esempio: se la stringa nel file eng.php è:
	// "FEEDBACK_USER_EMAIL_ALREADY_TAKEN" => "Sorry, the email $user_email is already in use." 
	// posso invocare il metodo:
	// Text::get('FEEDBACK_USER_EMAIL_ALREADY_TAKEN', ['user_email' => $user_email])

	public static function get($key, $data=null){
		
		// if not $key
		if (!$key) {
			return null;
		}
		
		if ($data) {
			foreach ($data as $var => $value) {
				${$var} = $value;
			}
		}
				
		// load config file (this is only done once per application lifecycle)
		if (!self::$texts) {
			self::$texts = require(__DIR__ . '/translations/it.php');			
			// php\php_iubar_fatture\www\php\Application\Services\Login\
		}
		// check if array key exists
		if (!array_key_exists($key, self::$texts)) {
			return null;
		}
		return self::$texts[$key];
	}
}