<?php
namespace Iubar\Login\Services;

class Text {

	private static $texts = null;
	
	// Esempio: se la stringa nel file eng.php è:
	// "FEEDBACK_USER_EMAIL_ALREADY_TAKEN" => "Sorry, the email $user_email is already in use."
	// posso invocare il metodo:
	// Text::get('FEEDBACK_USER_EMAIL_ALREADY_TAKEN', ['user_email' => $user_email])
	public static function get($key, $data = null) {
		
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
			$file = __DIR__ . '/translations/it.php';
			if (is_readable($file)) {
				self::$texts = require ($file);
			} else {
				throw new \RuntimeException('Can not load the translation file: ' . $file);
			}
		}
		// check if array key exists
		if (!array_key_exists($key, self::$texts)) {
			return null;
		}
		return self::$texts[$key];
	}
}