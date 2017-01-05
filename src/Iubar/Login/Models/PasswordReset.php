<?php

namespace Iubar\Login\Models;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Models\User as UserModel;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Services\Encryption;
use Iubar\Login\Core\EmailSender;
use \ReCaptcha\ReCaptcha;
use Iubar\Login\Models\AbstractLogin;

/**
 * Class PasswordResetModel
 *
 * Handles all the stuff that is related to the password-reset process
 */
class PasswordReset extends AbstractLogin {
	/**
	 * Perform the necessary actions to send a password reset mail
	 *
	 * @param $user_name_or_email string Username or user's email
	 *
	 * @return bool success status
	 */
	public static function requestPasswordReset($user_name_or_email, $captcha){	
		self::getLogger()->debug("This is registrationInputValidation()");
		$captcha_enabled = self::config('captcha.enabled');
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
		
		if (empty($user_name_or_email)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_EMAIL_FIELD_EMPTY'));
			return false;
		}
		// check if that username exists
		$user = UserModel::getUserDataByUserNameOrEmail($user_name_or_email);
		if (!$user) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
			return false;
		}
		// generate integer-timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
		// generate random hash for email password reset verification (40 char string)
		$temporary_timestamp = time();
		$user_password_reset_hash = sha1(uniqid(mt_rand(), true));
		// set token (= a random hash string and a timestamp) into database ...
		$token_set = self::setPasswordResetDatabaseToken($user->getUsername(), $user_password_reset_hash, $temporary_timestamp);
		if (!$token_set) {
			return false;
		}
		// ... and send a mail to the user, containing a link with username and token hash string
		$mail_sent = self::sendPasswordResetMail($user->getUsername(), $user_password_reset_hash, $user->getEmail());
		if ($mail_sent) {
			return true;
		}
		// default return
		return false;
	}
	/**
	 * Set password reset token in database (for DEFAULT user accounts)
	 *
	 * @param string $user_name username
	 * @param string $user_password_reset_hash password reset hash
	 * @param int $temporary_timestamp timestamp
	 *
	 * @return bool success status
	 */
	public static function setPasswordResetDatabaseToken($user_name, $user_password_reset_hash, $temporary_timestamp){
		
		$temporary_datetime = new \DateTime();
		$temporary_datetime->setTimestamp($temporary_timestamp);
		$datetime_string = $temporary_datetime->format('Y-m-d H:i:s');
		
		$dql = "UPDATE " . UserModel::TABLE_NAME . " u SET";
		$dql .=  " u.pwdresethash = '" . $user_password_reset_hash . "'";
		$dql .=  ", u.pwdresettime = '" . $datetime_string . "'";
		$dql .=  " WHERE u.username = '" . $user_name . "'";
		$dql .=  " AND u.providertype = '" . UserModel::PROVIDER_TYPE_DEFAULT. "'";
				
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
				
		// check if exactly one row was successfully changed
		if ($numUpdated == 1) {
			return true;
		}
		// fallback
		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_RESET_TOKEN_FAIL'));
		return false;
	}
	/**
	 * Send the password reset mail
	 *
	 * @param string $user_name username
	 * @param string $user_password_reset_hash password reset hash
	 * @param string $user_email user email
	 *
	 * @return bool success status
	 */
	public static function sendPasswordResetMail($user_name, $user_password_reset_hash, $user_email) {
		// create email body
		$url = self::config('app.baseurl') . '/' . self::config('email.pwdreset.url') . '/'  . urlencode($user_password_reset_hash) . "?user_name=" . urlencode(Encryption::encrypt($user_name));
		$subject = self::config('email.pwdreset.subject');
		$body = self::config('email.pwdreset.content') . ' <a href="'.$url.'">'.$url.'</a>';
		$mail = new EmailSender();
		$mail->setTo($user_email);
		$mail->setSubject($subject);
		$mail->setBodyHtml($body);
		$mail_sent = $mail->go(true);
		
		if ($mail_sent) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_PASSWORD_RESET_MAIL_SENDING_SUCCESSFUL'));
			return true;
		}
		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_RESET_MAIL_SENDING_ERROR'));
		return false;
	}
	
	/**
	 * Verifies the password reset request via the verification hash token (that's only valid for one hour)
	 * @param string $user_name Username
	 * @param string $verification_code Hash token
	 * @return bool Success status
	 */
	public static function verifyPasswordReset($user_name, $verification_code){ 
		// check if user-provided username + verification code combination exists
		
		$dql =  "SELECT u FROM " . UserModel::TABLE_NAME . " u WHERE";
		$dql .=  " u.username = '" . $user_name . "'";
		$dql .=  " AND u.pwdresethash = '" . $verification_code . "'";
		$dql .=  " AND u.providertype = '" . UserModel::PROVIDER_TYPE_DEFAULT . "'";

		$result = DbResource::getEntityManager()->createQuery($dql)->getResult();
		
		// if this user with exactly this verification hash code does NOT exist
		if (count($result) != 1) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_RESET_COMBINATION_DOES_NOT_EXIST'));
			return false;
		}
		// get result row (as an object)
		$user = array_shift($result);
		// 3600 seconds are 1 hour
		$timestamp_one_hour_ago = time() - 3600;
		// if password reset request was sent within the last hour (this timeout is for security reasons)
		$datetime = $user->getPwdresettime();		
		if(!$datetime){
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_UNKNOWN_ERROR'));
			return false;
		}else{
			$user_password_reset_timestamp = $datetime->getTimestamp();
			if ($user_password_reset_timestamp > $timestamp_one_hour_ago) {
				// verification was successful
				Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_PASSWORD_RESET_LINK_VALID'));
				return true;
			} else {
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_RESET_LINK_EXPIRED'));
				return false;
			}
		}
	}
	/**
	 * Writes the new password to the database
	 *
	 * @param string $user_name username
	 * @param string $user_password_hash
	 * @param string $user_password_reset_hash
	 *
	 * @return bool
	 */
	public static function saveNewUserPassword($user_name, $user_password_hash, $user_password_reset_hash){
		
		$dql = "UPDATE " . UserModel::TABLE_NAME . " u SET";
		$dql .=  " u.pwdhash = '" . $user_password_hash . "'";
		$dql .=  ", u.pwdresethash = NULL";
		$dql .=  ", u.pwdresettime = NULL";		
		$dql .=  " WHERE u.username = '" . $user_name . "'";
		$dql .=  " AND u.pwdresethash = '" . $user_password_reset_hash . "'";
		$dql .=  " AND u.providertype = '" . UserModel::PROVIDER_TYPE_DEFAULT. "'";
		
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
		
		// if one result exists, return true, else false. Could be written even shorter btw.
		return ($numUpdated == 1 ? true : false);
	}
	/**
	 * Set the new password (for DEFAULT user, FACEBOOK-users don't have a password)
	 * Please note: At this point the user has already pre-verified via verifyPasswordReset() (within one hour),
	 * so we don't need to check again for the 60min-limit here. In this method we authenticate
	 * via username & password-reset-hash from (hidden) form fields.
	 *
	 * @param string $user_name
	 * @param string $user_password_reset_hash
	 * @param string $user_password_new
	 * @param string $user_password_repeat
	 *
	 * @return bool success state of the password reset
	 */
	public static function setNewPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat) {
		// validate the password
		if (!self::validateResetPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)) {
			return false;
		}
		// crypt the password (with the PHP 5.5+'s password_hash() function, result is a 60 character hash string)
		$user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);
		// write the password to database (as hashed and salted string), reset user_password_reset_hash
		if (self::saveNewUserPassword($user_name, $user_password_hash, $user_password_reset_hash)) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL'));
			return true;
		} else {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_CHANGE_FAILED'));
			return false;
		}
	}
	/**
	 * Validate the password submission
	 *
	 * @param $user_name
	 * @param $user_password_reset_hash
	 * @param $user_password_new
	 * @param $user_password_repeat
	 *
	 * @return bool
	 */
	public static function validateResetPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat){
		if (empty($user_name)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_FIELD_EMPTY'));
			return false;
		} else if (empty($user_password_reset_hash)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_RESET_TOKEN_MISSING'));
			return false;
		} else if (empty($user_password_new) || empty($user_password_repeat)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
			return false;
		} else if ($user_password_new !== $user_password_repeat) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
			return false;
		} else if (strlen($user_password_new) < 6) { // TODO: parametrizzare numero minimo di caratteri per password
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
			return false;
		}
		return true;
	}
	/**
	 * Writes the new password to the database
	 *
	 * @param string $user_name
	 * @param string $user_password_hash
	 *
	 * @return bool
	 */
	public static function saveChangedPassword($user_name, $user_password_hash){
		
		$dql = "UPDATE " . UserModel::TABLE_NAME . " u SET";
		$dql .=  " u.pwdhash = '" . $user_password_hash . "'";
		$dql .=  " WHERE u.username = '" . $user_name . "'";
		$dql .=  " AND u.providertype = '" . UserModel::PROVIDER_TYPE_DEFAULT. "'";
		
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
		
		// if one result exists, return true, else false. Could be written even shorter btw.
		return ($numUpdated == 1 ? true : false);
	}
	/**
	 * Validates fields, hashes new password, saves new password
	 *
	 * @param string $user_name
	 * @param string $user_password_current
	 * @param string $user_password_new
	 * @param string $user_password_repeat
	 *
	 * @return bool
	 */
	public static function changePassword($user_name, $user_password_current, $user_password_new, $user_password_repeat){
		// validate the passwords
		if (!self::validatePasswordChange($user_name, $user_password_current, $user_password_new, $user_password_repeat)) {
			return false;
		}
		// crypt the password (with the PHP 5.5+'s password_hash() function, result is a 60 character hash string)
		$user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);
		// write the password to database (as hashed and salted string)
		if (self::saveChangedPassword($user_name, $user_password_hash)) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL'));
			return true;
		} else {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_CHANGE_FAILED'));
			return false;
		}
	}
	/**
	 * Validates current and new passwords
	 *
	 * @param string $user_name
	 * @param string $user_password_current
	 * @param string $user_password_new
	 * @param string $user_password_repeat
	 *
	 * @return bool
	 */
	public static function validatePasswordChange($user_name, $user_password_current, $user_password_new, $user_password_repeat){
		$user = UserModel::getByUsername($user_name);

        if ($user) {
            $user_password_hash = $user->getPwdhash();
        } else {
            Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
            return false;
        }
		if (!password_verify($user_password_current, $user_password_hash)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_CURRENT_INCORRECT'));
			return false;
		} else if (empty($user_password_new) || empty($user_password_repeat)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
			return false;
		} else if ($user_password_new !== $user_password_repeat) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
			return false;
		} else if (strlen($user_password_new) < 6) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
			return false;
		} else if ($user_password_current == $user_password_new){
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_NEW_SAME_AS_CURRENT'));
			return false;
		}
		return true;
	}
}