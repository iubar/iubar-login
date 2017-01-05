<?php

namespace Iubar\Login\Models;

use Iubar\Login\Models\User;
use Iubar\Login\Core\DbResource;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Services\Mail;
use Iubar\Login\Services\Encryption;
use Iubar\Login\Core\EmailSender;
use Iubar\Login\Services\ApiKey;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Models\UserRole;
use \ReCaptcha\ReCaptcha;

/**
 * Class RegistrationModel
 *
 * Everything registration-related happens here.
 */
class Registration extends AbstractLogin {
	
	/**
	 * Handles the entire registration process for DEFAULT users (not for people who register with
	 * 3rd party services, like facebook) and creates a new user in the database if everything is fine
	 *
	 * @return boolean Gives back the success status of the registration
	 */
	public static function registerNewUser($user_name, $user_email, $user_email_repeat, $user_password_new, $user_password_repeat, $captcha, $provider_type){

		$user_password_hash = null;
		$user_activation_hash = null;

		self::getLogger()->debug("This is registerNewUser()");

		if(self::isDefaultProvider($provider_type)){
			// stop registration flow if registrationInputValidation() returns false (= anything breaks the input check rules)
			$validation_result = self::registrationInputValidation($user_name, $user_password_new, $user_password_repeat, $user_email, $user_email_repeat, $captcha);
			if (!$validation_result) {
				self::getLogger()->debug("ERROR: registrationInputValidation() failed");
				return false;
			}

			self::getLogger()->debug("OK: registrationInputValidation() returns true");

			// crypt the password with the PHP 5.5's password_hash() function, results in a 60 character hash string.
			// @see php.net/manual/en/function.password-hash.php for more, especially for potential options
			$user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);

			self::getLogger()->debug("\$user_password_hash: " . $user_password_hash);

			if (self::config('auth.email.verification.enabled')){
				// generate random hash for email verification (40 char string)
				$user_activation_hash = sha1(uniqid(mt_rand(), true));
			}
		}

		// check if username already exists
		if (User::getByUsername($user_name) !== null) {
			self::getLogger()->debug("Error: Username non disponibile");
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_ALREADY_TAKEN'));
			return false;
		}

		self::getLogger()->debug("OK: username doesn't exists");

		// check if email already exists
		if (User::getByEmail($user_email) !== null) {
			self::getLogger()->debug('Email in uso');
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USER_EMAIL_ALREADY_TAKEN'));
			return false;
		}

		self::getLogger()->debug("OK: email doesn't exists");

		// write user data to database
		if (!self::writeNewUserToDatabase($user_name, $user_password_hash, $user_email, $user_activation_hash, $provider_type)) {
			self::getLogger()->debug('Registrazione fallita');
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_CREATION_FAILED'));
			return false;
		}

		self::getLogger()->debug("OK: writeNewUserToDatabase() returns true");

		$user = User::getByEmail($user_email); // get user_id of the user that has been created
		if (!$user) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_UNKNOWN_ERROR'));
			return false;
		}

		if(self::isDefaultProvider($provider_type) && self::config('auth.email.verification.enabled')){
			
			// send verification email
			if (self::sendVerificationEmail($user_name, $user_email, $user_activation_hash)) {
				self::getLogger()->debug("OK: verification email sent to " . $user_email);
				Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_ACCOUNT_SUCCESSFULLY_CREATED'));
				return true;
			}

			self::getLogger()->debug("ERROR: sending verification email to " . $user_email . " failed");

			// if verification email sending failed: instantly delete the user
			self::rollbackRegistrationByUsername($user_name);
			self::getLogger()->debug("NOTICE: rollbackRegistrationByUsername()");
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_VERIFICATION_MAIL_SENDING_FAILED'));
			return false;

		}else{
			if (self::sendWelcomeEmail($user_name, $user_email)){
				return true;
			}
			self::getLogger()->debug("ERROR: sending welcome email to " . $user_email . " failed");
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_WELCOME_MAIL_SENDING_FAILED'));
			return false;
		}

		return false;
	}

	/**
	 * Validates the registration input
	 *
	 * @param $captcha
	 * @param $user_name
	 * @param $user_password_new
	 * @param $user_password_repeat
	 * @param $user_email
	 * @param $user_email_repeat
	 * @param $captcha
	 *
	 * @return bool
	 */
	private static function registrationInputValidation($user_name, $user_password_new, $user_password_repeat, $user_email, $user_email_repeat, $captcha) {
		self::getLogger()->debug("This is registrationInputValidation()");
		$captcha_enabled = self::config('captcha.enabled');
		// NOTA: non si usa $captcha_enabled = self::config('RECAPTCHA_ENABLED'); perchè bisogna integrarsi con slim per gestire i template twig nelle sezioni relative a recaptcha
		if($captcha_enabled){ 
 			// perform all necessary checks
			$secret = self::config('captcha.secret');
			$recaptcha = new ReCaptcha($secret);
			$resp = $recaptcha->verify($captcha, $_SERVER['REMOTE_ADDR']);
			if ($resp->isSuccess()) {
				self::getLogger()->debug("captcha ok");
			} else {
				$errors = $resp->getErrorCodes();
				self::getLogger()->debug("wrong captcha", $errors);
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_CAPTCHA_WRONG'));
				return false;
			}
		}

		// if username, email and password are all correctly validated
		// era: self::validateUserName($user_name) AND ...
		if (self::validateUserEmail($user_email, $user_email_repeat) AND self::validateUserPassword($user_password_new, $user_password_repeat)) {
			return true;
		}

		// otherwise, return false
		return false;
	}

    /**
     * Validates the username
     *
     * @param $user_name
     * @return bool
     */
    private static function validateUserName($user_name){
    	self::getLogger()->debug("This is validateUserName()");
        if (empty($user_name)) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_FIELD_EMPTY'));
            return false;
        }
        // if username is too short (2), too long (64) or does not fit the pattern (aZ09)
        if (!preg_match('/^[a-zA-Z0-9]{2,64}$/', $user_name)) {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_DOES_NOT_FIT_PATTERN'));
            return false;
        }
        return true;
    }
    /**
     * Validates the email
     *
     * @param $user_email
     * @param $user_email_repeat
     * @return bool
     */
	private static function validateUserEmail($user_email, $user_email_repeat=null){

		self::getLogger()->debug("This is validateUserEmail()");

		if (empty($user_email)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_EMAIL_FIELD_EMPTY'));
			return false;
		}

		if ($user_email !== $user_email_repeat && $user_email_repeat !== null) {
 			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_EMAIL_REPEAT_WRONG'));
 			return false;
 		}

		// validate the email with PHP's internal filter
		// side-fact: Max length seems to be 254 chars
		// @see http://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address
		if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) { // TODO: validate il dominio dell'indirizzo email
			self::getLogger()->debug("Error: email is not valid");
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_EMAIL_DOES_NOT_FIT_PATTERN'));
			return false;
		}

		return true;
	}

    /**
     * Validates the password
     *
     * @param $user_password_new
     * @param $user_password_repeat
     * @return bool
     */
    private static function validateUserPassword($user_password_new, $user_password_repeat){

    	self::getLogger()->debug("This is validateUserPassword()");

        if (empty($user_password_new) OR empty($user_password_repeat)) {
        	self::getLogger()->debug('Password obbligatoria');
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
            return false;
        }
        if ($user_password_new !== $user_password_repeat) {
        	self::getLogger()->debug('Le password non corrispondono');
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
            return false;
        }
        if (strlen($user_password_new) < 6) {
        	self::getLogger()->debug('Password troppo corta');
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
            return false;
        }
        return true;
    }
    
	/**
	 * Writes the new user's data to the database
	 *
	 * @param $user_name
	 * @param $user_password_hash
	 * @param $user_email
	 * @param $user_activation_hash
	 *
	 * @return bool
	 */
	private static function writeNewUserToDatabase($user_name, $user_password_hash, $user_email, $user_activation_hash, $provider_type){

		$user = new User();
		$user->setIdpersonafisica($pf);
		$user->setUsername($user_name);
		$user->setEmail($user_email);
		$now = new \DateTime();
		$now->setTimestamp(time());
		$user->setCreationtime($now);
		$user->setAccounttype(UserRole::READ_WRITE);
		if(self::isDefaultProvider($provider_type)){
			$user->setPwdhash($user_password_hash);
			if (self::config('auth.email.verification.enabled')){
				$user->setActivationhash($user_activation_hash);
			} else {
				$user->setActive(true);
			}
		}else{
			$user->setActive(true); // solo se il provider è esterno, attivo l'utente senza inviare email di conferma
		}
		$ip = self::getRequestIp();
		$user->setCreationip($ip);
		$user->setProvidertype($provider_type);
		$user->setApikey(ApiKey::create());

		User::save($user);

		return true;

	}

	/**
	 * Deletes the user from users table. Currently used to rollback a registration when verification mail sending
	 * was not successful.
	 *
	 * @param $user_name
	 */
	private static function rollbackRegistrationByUsername($user_name){
		$dql = "DELETE FROM " . User::TABLE_NAME . " u WHERE u.username = '" . $user_name . "'";
		$numDeleted = DbResource::getEntityManager()->createQuery($dql)->execute();
		return $numDeleted;
	}

	public static function rollbackRegistrationByEmail($user_email){
		$dql = "DELETE FROM " . User::TABLE_NAME . " u WHERE u.email = '" . $user_email . "'";
		$numDeleted = DbResource::getEntityManager()->createQuery($dql)->execute();
		return $numDeleted;
	}

	/**
	 * Sends the verification email (to confirm the account).
	 * The construction of the mail $body looks weird at first, but it's really just a simple string.
	 *
	 * @param string $user_name
	 * @param string $user_email user's email
	 * @param string $user_activation_hash user's mail verification hash string
	 *
	 * @return boolean gives back true if mail has been sent, gives back false if no mail could been sent
	 */
 	private static function sendVerificationEmail($user_name, $user_email, $user_activation_hash){
 		
		$url = self::config('app.baseurl') . '/' . self::config('email.verification.url')
			. '/' . urlencode($user_activation_hash) . "?user_name=" . urlencode(Encryption::encrypt($user_name));
		
		$subject = self::config('email.verification.subject');
		$body = self::config('email.verification.content') . ' <a href="'.$url.'">'.$url.'</a>';
		
		$mail = new EmailSender();
		$mail->setTo($user_email);
		$mail->setSubject($subject);
		$mail->setBodyHtml($body);
		$mail_sent = $mail->go(true);
		
		if ($mail_sent) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_VERIFICATION_MAIL_SENDING_SUCCESSFUL'));
			return true;
		} else {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_VERIFICATION_MAIL_SENDING_ERROR'));
			return false;
		}
	}

	private static function sendWelcomeEmail($user_name, $user_email){
		$subject = self::config('email.welcome.subject');
		$body = self::config('email.welcome.content');

		$mail = new EmailSender();
		$mail->setTo($user_email);
		$mail->setSubject($subject);
		$mail->setBodyHtml($body);
		$mail_sent = $mail->go(true);
		
		if ($mail_sent) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_WELCOME_MAIL_SENDING_SUCCESSFUL'));
			return true;
		} else {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_WELCOME_MAIL_SENDING_ERROR'));
			return false;
		}
	}

	/**
	 * checks the email/verification code combination and set the user's activation status to true in the database
	 *
	 * @param string $user_name
	 * @param string $ua_verification_code verification token
	 *
	 * @return bool success status
	 */
	public static function verifyNewUser($user_name, $ua_verification_code) {
		$dql = "UPDATE " . User::TABLE_NAME . " u SET u.active = 1, u.activationhash = NULL WHERE u.username = '" . $user_name . "' AND u.activationhash = '" . $ua_verification_code . "'";
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
		if ($numUpdated == 1) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_ACCOUNT_ACTIVATION_SUCCESSFUL'));
			return true;
		}
		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_ACTIVATION_FAILED'));
		return false;
	}

	public static function isDefaultProvider($provider_type){
		$b = false;
		if ($provider_type == User::PROVIDER_TYPE_DEFAULT){
	    	$b = true;
	    }
		
	    return $b;
	}

}
