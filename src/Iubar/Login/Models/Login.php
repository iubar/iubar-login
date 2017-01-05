<?php

namespace Iubar\Login\Models;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Models\User as UserModel;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Services\Encryption;
use Iubar\Login\Services\Filter;
use Iubar\Login\Models\Avatar as AvatarModel;
use Iubar\Login\Models\AbstractLogin;

class Login extends AbstractLogin {
	
	const COOKIE_REMEMBER_ME = 'remember_me';
	
	/**
	 * Login process (for DEFAULT user accounts).
	 *
	 * @param $user_name string The user's name
	 * @param $user_password string The user's password
	 * @param $set_remember_me_cookie mixed Marker for usage of remember-me cookie feature
	 *
	 * @return bool success state
	 */
	public static function login($user_name, $user_password, $set_remember_me_cookie, $provider_type)
	{
		
		if(!UserModel::isExternalProvider($provider_type)){
			// we do negative-first checks here, for simplicity empty username and empty password in one line
			if (empty($user_name) OR empty($user_password)) {
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_OR_PASSWORD_FIELD_EMPTY'));
				return false;
			}
		}
			
		// checks if user exists, if login is not blocked (due to failed logins) and if password fits the hash
		$user = self::validateAndGetUser($user_name, $user_password, $provider_type);
		if (!$user) {
			// No Need to give feedback here since whole validateAndGetUser controls gives a feedback
			return false;			
		}
		
		// stop the user's login if account has been soft deleted
		if ($user->getDeleted() == 1) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_DELETED'));
			return false;
		}
		
		// stop the user from logging in if user has a suspension, display how long they have left in the feedback.
		if ($user->getSuspensiontime() != null && $user->getSuspensiontime() - time() > 0) {
			$suspensionTimer = Text::get('FEEDBACK_ACCOUNT_SUSPENDED') . round(abs($result->getSuspensiontime() - time())/60/60, 2) . " hours left"; // TODO: messaggio di testo non localizzato
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, $suspensionTimer);
			return false;
		}		
	
		// reset the failed login counter for that user (if necessary)
		if ($user->getLastfailedlogin()) {
			self::resetFailedLoginCounterOfUser($user_name);
		}
	
		// save timestamp of this login in the database line of that user
		self::saveTimestampOfLoginOfUser($user_name);
	
		if(!UserModel::isExternalAccount($user)){
			// if user has checked the "remember me" checkbox, then write token into database and into cookie
			if ($set_remember_me_cookie) {
				self::setRememberMeInDatabaseAndCookie($user_name);
			}
		}
				
		// successfully logged in, so we write all necessary data into the session and set "user_logged_in" to true
		self::setSuccessfulLoginIntoSession($user->getUsername(), $user->getEmail(), $user->getAccounttype(), $user->getProvidertype());
	
		// return true to make clear the login was successful
		// maybe do this in dependence of setSuccessfulLoginIntoSession ?
			
		return true;
	}
	
	public static function loginWithEmail($user_email, $user_password, $set_remember_me_cookie){
		$user = UserModel::getByEmail($user_email);
		if($user){
			$user_name = $user->getUsername();
			return self::login($user_name, $user_password, $set_remember_me_cookie, UserModel::PROVIDER_TYPE_DEFAULT);
		}
		return false;
	}
	
	public static function loginExternal($user_email, $provider_type){
		$user = UserModel::getByEmail($user_email);
		if($user){
			$user_name = $user->getUsername();
			return self::login($user_name, null, null, $provider_type);
		}else{
			throw new \RuntimeException("loginExternal(): user is null");
		}
		return false;
	}
	
	/**
	 * Validates the inputs of the users, checks if password is correct etc.
	 * If successful, user is returned
	 *
	 * @param $user_name
	 * @param $user_password
	 *
	 * @return bool|mixed
	 */
	private static function validateAndGetUser($user_name, $user_password, $provider_type){
		
		// we do negative-first checks here, for simplicity empty username and empty password in one line
		if (empty($user_name)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_FIELD_EMPTY'));
			return false;
		}else if (empty($user_password) && ($provider_type != UserModel::PROVIDER_TYPE_FB && $provider_type != UserModel::PROVIDER_TYPE_GO)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
			return false;
		}
		
		self::getLogger()->debug("validateAndGetUser: " . $user_name);
		
		// brute force attack mitigation: use session failed login count and last failed login for not found users.
		// block login attempt if somebody has already failed 3 times and the last login attempt is less than 30sec ago
		// (limits user searches in database)
		if (Session::get(Session::SESSION_FAILED_LOGIN_COUNT) >= 3 AND (Session::get(Session::SESSION_LAST_FAILED_LOGIN) > (time() - 30))) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_LOGIN_FAILED_3_TIMES'));
			return false;
		}
		
		// get all data of that user (to later check if password and password_hash fit)
		$user = UserModel::getByUsername($user_name);
	
		// Check if that user exists. We don't give back a cause in the feedback to avoid giving an attacker details.
		if (!$user) {
			// increment the user not found count, helps mitigate user enumeration
			self::incrementUserNotFoundCounter();			
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_OR_PASSWORD_WRONG'));
			return false;
		}else{
			self::getLogger()->debug("User obj fetched");
		}
	
		// block login attempt if somebody has already failed 3 times and the last login attempt is less than 30sec ago
		$datetime = $user->getLastfailedlogin();
		if (($user->getFailedlogins() >= 3) AND ($datetime && $datetime->getTimestamp() > (time() - 30))) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_LOGIN_FAILED_3_TIMES')); // 'Hai sbagliato la password 3 volte consecutive. Attendi 30 secondi'
			return false;
		}
							
		if(!UserModel::isExternalAccount($user)){ // Gli utenti FB o Google non hanno password			
			if(!$user_password){
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_UNKNOWN_ERROR'));
				return false;
			}
			
			// if hash of provided password does NOT match the hash in the database: +1 failed-login counter
			if (!password_verify($user_password, $user->getPwdhash())) { // http://php.net/manual/en/function.password-verify.php
				self::incrementFailedLoginCounterOfUser($user_name);
				// we say "password wrong" here, but less details like "login failed" would be better (= less information)
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_OR_PASSWORD_WRONG'));
				return false;
			}
		}

		// if user is not active (= has not verified account by verification mail)
		if ($user->getActive() != 1) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_NOT_ACTIVATED_YET'));
			return false;
		}

		// reset the user not found counter
		self::resetUserNotFoundCounter();
				
		return $user;
	}
	

	/**
	 * Reset the failed-login-count to 0.
	 * Reset the last-failed-login to an empty string.
	 */
	private static function resetUserNotFoundCounter(){
		Session::set(Session::SESSION_FAILED_LOGIN_COUNT, 0);
		Session::set(Session::SESSION_LAST_FAILED_LOGIN, '');
	}
	
	/**
	 * Increment the failed-login-count by 1.
	 * Add timestamp to last-failed-login.
	 */
	private static function incrementUserNotFoundCounter()
	{
		// Username enumeration prevention: set session failed login count and last failed login for users not found
		Session::set(Session::SESSION_FAILED_LOGIN_COUNT, Session::get(Session::SESSION_FAILED_LOGIN_COUNT) + 1);
		Session::set(Session::SESSION_LAST_FAILED_LOGIN, time());
	}
	
	
	/**
	 * performs the login via cookie (for DEFAULT user account, FACEBOOK-accounts are handled differently)
	 * TODO add throttling here ?
	 *
	 * @param $cookie string The cookie "remember_me"
	 *
	 * @return bool success state
	 */
	public static function loginWithCookie($cookie){
		// do we have a cookie ?
		if (!$cookie) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_COOKIE_INVALID'));
			return false;
		}
	
		// before list(), check it can be split into 3 strings.
		if(count (explode(':', $cookie)) !== 3){
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_COOKIE_INVALID'));
			return false;
		}
		
		// check cookie's contents, check if cookie contents belong together or token is empty
		list ($user_name, $token, $hash) = explode(':', $cookie);
		
		// decrypt user user_name
		$user_name = Encryption::decrypt($user_name);
		
		if ($hash !== hash('sha256', $user_name . ':' . $token) OR empty($token) OR empty($user_name)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_COOKIE_INVALID'));
			return false;
		}
	
		// get data of user that has this id and this token
		$user = UserModel::getUserDataByUserNameAndToken($user_name, $token);
		
		// if user with that id and exactly that cookie token exists in database
		if ($user) {
			// successfully logged in, so we write all necessary data into the session and set "user_logged_in" to true
			self::setSuccessfulLoginIntoSession($user->getUsername(), $user->getEmail(), $user->getAccounttype(), $user->getProvidertype());
			// save timestamp of this login in the database line of that user
			self::saveTimestampOfLoginOfUser($user->getUsername());
	

			// NOTE: we don't set another remember_me-cookie here as the current cookie should always
			// be invalid after a certain amount of time, so the user has to login with username/password
			// again from time to time. This is good and safe ! ;)
			
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_COOKIE_LOGIN_SUCCESSFUL'));
			return true;
		} else {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_COOKIE_INVALID'));
			return false;
		}
	}
	
	/**
	 * Log out process: delete cookie, delete session
	 */
	public static function logout(){
 		
		$user_name = Session::getDecoded(Session::SESSION_USER_NAME);		
		$user_provider = Session::get(Session::SESSION_USER_PROVIDER_TYPE);
		
		if($user_provider==UserModel::PROVIDER_TYPE_FB){
			// Facebook
// 			Session::set(Session::FACEBOOK_ID, null);
// 			Session::set(Session::FACEBOOK_ACCESS_TOKEN, null);
// 			Session::set(Session::FACEBOOK_DISPLAY_NAME, null);
// 			Session::set(Session::FACEBOOK_PICTURE, null);								
		}else if($user_provider==UserModel::PROVIDER_TYPE_GO){
// 			Session::set(Session::GOOGLE_ID, null);
// 			Session::set(Session::GOOGLE_JWT_TOKEN, null);
// 			Session::set(Session::GOOGLE_DISPLAY_NAME, null);			
// 			Session::set(Session::GOOGLE_PICTURE, null);
// 			Session::set(Session::GOOGLE_ACCESS_TOKEN, null);
// 			Session::set(Session::GOOGLE_REFRESH_TOKEN, null);
		}else{
			self::deleteCookie($user_name); // solo per provider 'DEFAULT'			
		}
				
		Session::destroy();
		Session::updateSessionId($user_name, null);
					
// 		if(false){ // Il seguente blocco è inutile (vedi statement successivi)
// 			Session::set(Session::SESSION_FEEDBACK_NEGATIVE, null);
// 			Session::set(Session::SESSION_FEEDBACK_POSITIVE, null);
// 			Session::set(Session::SESSION_USER_NAME, null);
// 			Session::set(Session::SESSION_USER_EMAIL, null);
// 			Session::set(Session::SESSION_USER_ACCOUNT_TYPE, null);
// 			Session::set(Session::SESSION_USER_PROVIDER_TYPE, null);
// 			Session::set(Session::SESSION_USER_AVATAR_FILE, null);
// 			Session::set(Session::SESSION_USER_GRAVATAR_IMAGE_URL, null);
// 			Session::set(Session::SESSION_USER_LOGGED_IN, null);
// 		}
		

		return true;
	}
	
	/**
	 * The real login process: The user's data is written into the session.
	 * Cheesy name, maybe rename. Also maybe refactoring this, using an array.
	 *
	 * @param $user_name
	 * @param $user_name
	 * @param $user_email
	 * @param $user_account_type
	 */
	public static function setSuccessfulLoginIntoSession($user_name, $user_email, $user_account_type, $user_provider_type){

		Session::regenerateId(); //  Update the current session id with a newly generated one
				
		Session::set(Session::SESSION_USER_NAME, $user_name);
		Session::set(Session::SESSION_USER_EMAIL, $user_email);
		Session::set(Session::SESSION_USER_ACCOUNT_TYPE, $user_account_type);
		Session::set(Session::SESSION_USER_PROVIDER_TYPE, $user_provider_type);
	
 		// get and set avatars
 		Session::set(Session::SESSION_USER_AVATAR_FILE, AvatarModel::getPublicUserAvatarFilePathByUserName($user_name));
 		Session::set(Session::SESSION_USER_GRAVATAR_IMAGE_URL, AvatarModel::getGravatarLinkByEmail($user_email));
	
		// finally, set user as logged-in
		Session::set(Session::SESSION_USER_LOGGED_IN, true);
		
		// update session id in database
		Session::updateSessionId($user_name, session_id());
		
		// set session cookie setting manually,
		// Why? because you need to explicitly set session expiry, path, domain, secure, and HTTP.
		// @see https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet#Cookies
		setcookie(session_name(), session_id(), time() + self::config('session.runtime'), self::config('cookie.path'),
				self::config('cookie.domain'), self::config('cookie.secure'), self::config('cookie.http'));
		
		self::getLogger()->debug("Session name: " . session_name() . " id: " . session_id());
	}
	
	/**
	 * Increments the failed-login counter of a user
	 *
	 * @param $user_name
	 */
	public static function incrementFailedLoginCounterOfUser($user_name){
		$user = UserModel::getByUsername($user_name);
		$user->setFailedlogins($user->getFailedlogins() + 1);
		$user->setLastfailedlogin(new \DateTime());
	
		$em = DbResource::getEntityManager();	
		$em->persist($user);
		$em->flush();
	}
	
	/**
	 * Resets the failed-login counter of a user back to 0
	 *
	 * @param $user_name
	 */
	public static function resetFailedLoginCounterOfUser($user_name) {
		$user = UserModel::getByUsername($user_name);
		$user->setFailedlogins(0);
		$user->setLastfailedlogin(null);
	
		$em = DbResource::getEntityManager();		 
		$em->persist($user);
		$em->flush();
	}
	
	/**
	 * Write timestamp of this login into database (we only write a "real" login via login form into the database,
	 * not the session-login on every page request
	 *
	 * @param $user_name
	 */
	public static function saveTimestampOfLoginOfUser($user_name){

		$user = UserModel::getByUsername($user_name);
		$user->setLastlogin(new \DateTime());
		$user->setLastIp(self::getRequestIp());		
		$em = DbResource::getEntityManager();		 
		$em->persist($user);
		$em->flush();
	}
	
	/**
	 * Write remember-me token into database and into cookie
	 * Maybe splitting this into database and cookie part ?
	 *
	 * @param $user_name string
	 */
	public static function setRememberMeInDatabaseAndCookie($user_name){
		$user = UserModel::getByUsername($user_name);
		
		// generate 64 char random string
		$random_token_string = hash('sha256', mt_rand());
	
		// write that token into database
		$user->setRemembermetoken($random_token_string); 
		
		$em = DbResource::getEntityManager();
		$em->persist($user);
		$em->flush();		
	
        // generate cookie string that consists of user id, random string and combined hash of both
        // never expose the original user id, instead, encrypt it.
        $cookie_string_first_part = Encryption::encrypt($user_name) . ':' . $random_token_string;
        $cookie_string_hash       = hash('sha256', $user_name . ':' . $random_token_string);
        $cookie_string            = $cookie_string_first_part . ':' . $cookie_string_hash;
	
		// set cookie, and make it available only for the domain created on (to avoid XSS attacks, where the
        // attacker could steal your remember-me cookie string and would login itself).
        // If you are using HTTPS, then you should set the "secure" flag (the second one from right) to true, too.
        // @see http://www.php.net/manual/en/function.setcookie.php
        setcookie(self::COOKIE_REMEMBER_ME, $cookie_string, time() + self::config('cookie.runtime'), self::config('cookie.path'),
            self::config('cookie.domain'), self::config('cookie.secure'), self::config('cookie.http'));
	}
	
	/**
	 * Deletes the cookie
	 * It's necessary to split deleteCookie() and logout() as cookies are deleted without logging out too!
	 * Sets the remember-me-cookie to ten years ago (3600sec * 24 hours * 365 days * 10).
	 * that's obviously the best practice to kill a cookie @see http://stackoverflow.com/a/686166/1114320
	 */
	public static function deleteCookie($user_name=null){
		// is $user_name was set, then clear remember_me token in database
	    if($user_name){
	    	$user_name = Filter::html_entity_invert($user_name);
		 	$user = UserModel::getByUsername($user_name);
		 	$user->setRemembermetoken(NULL);
		 	$em = DbResource::getEntityManager();
		 	$em->persist($user);
		 	$em->flush();
	    }
	    
        // delete remember_me cookie in browser
        setcookie(self::COOKIE_REMEMBER_ME, false, time() - (3600 * 24 * 3650), self::config('cookie.path'),
            self::config('cookie.domain'), self::config('cookie.secure'), self::config('cookie.http'));
    }
	
	/**
	 * Returns the current state of the user's login
	 *
	 * @return bool user's login status
	 */
	public static function isUserLoggedIn(){
		$b = Session::userIsLoggedIn();
		if($b && utilizzo provider esterno){			
		// L'autenticazione Panique è basta sulle Sessioni è quindi stateful mentre quella di google e Facebook è stateless.
		// Per aumentare la sicurezza posso verificare se il token con cui è stato effettuato il login è scaduto		
			if (token è scaduto){
				$b = false;
				rinnova
				if(fallito){
					logout
				}else{
					store
					$b=true;
				}
			}
		}
		return $b;
	}

	
}