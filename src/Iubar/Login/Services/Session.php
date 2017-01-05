<?php

namespace Iubar\Login\Services;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Models\User as UserModel;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Services\Filter;


/**
 * Session class
 *
 * handles the session stuff. creates session when no one exists, sets and gets values, and closes the session
 * properly (=logout). Not to forget the check if the user is logged in or not.
 */
class Session {
	
	const SESSION_FEEDBACK_NEGATIVE = 'feedback_negative';
	const SESSION_FEEDBACK_POSITIVE = 'feedback_positive';
	const SESSION_USER_NAME = 'user_name';
	const SESSION_USER_EMAIL = 'user_email';
	const SESSION_USER_ACCOUNT_TYPE = 'user_account_type';
	const SESSION_USER_PROVIDER_TYPE = 'user_provider_type';
	const SESSION_USER_AVATAR_FILE = 'user_avatar_file';
	const SESSION_USER_GRAVATAR_IMAGE_URL = 'user_gravatar_image_url';
	const SESSION_USER_LOGGED_IN = 'user_logged_in';
	
	const SESSION_FAILED_LOGIN_COUNT = 'failed-login-count';
	const SESSION_LAST_FAILED_LOGIN = 'last-failed-login';
	
	const SESSION_CSRF_TOKEN = 'csrf_token';
	const SESSION_CSRF_TIME = 'csrf_token_time';
	
	// FACEBOOK
	const FACEBOOK_ID = 'facebook_id';
	const FACEBOOK_ACCESS_TOKEN = 'facebook_access_token';
	const FACEBOOK_DISPLAY_NAME = 'facebook_display_name';
	const FACEBOOK_PICTURE = 'facebook_picture';
	
	// GOOGLE
	const GOOGLE_ID = 'google_id';
	const GOOGLE_JWT_TOKEN = 'google_jwt_token';	
	const GOOGLE_DISPLAY_NAME = 'google_display_name';
	const GOOGLE_PICTURE = 'google_picture';
	const GOOGLE_ACCESS_TOKEN = 'google_access_token';
	const GOOGLE_REFRESH_TOKEN = 'google_refresh_token';
	
	// CUSTOM
	const SESSION_DISPLAY_NAME = 'display_name';
	
    /**
     * starts the session
     */
    public static function init(){
        // if no session exist, start the session
        // if (!session_id()) {
        if(session_status() !== PHP_SESSION_ACTIVE){        	
        	// You should also disable PHP’s session cache limiter so that PHP does not send conflicting cache expiration headers with the HTTP response
        	session_cache_limiter(false); // vedi http://docs.slimframework.com/sessions/native/
            session_start();
            self::getLogger()->debug("Session start: " . session_name() . " id: " . session_id());
        }else{
        	self::getLogger()->debug("Session already started: " . session_name() . " status: " . session_status() . " id: " . session_id());
        }
    }
    
	protected function getLogger(){
		return AbstractLogin::getLogger();
	}
	
    public static function regenerateId(){
    	// remove old and regenerate session ID.
    	// It's important to regenerate session on sensitive actions,
    	// and to avoid fixated session.
    	// e.g. when a user logs in
    	//
    	// It mainly helps prevent session fixation attacks. Session fixation attacks is where a malicious user tries to exploit the vulnerability in a system to fixate (set) the session ID (SID) of another user. By doing so, they will get complete access as the original user and be able to do tasks that would otherwise require authentication.
    	//
    	// Session hijacking refers to stealing the session cookie. This can be most easily accomplished when sharing a local network with other computers. E.g. at Starbucks. Example... a user with session Y is browsing James's website at Starbucks. I am listening in on their network traffic, sipping my latte. I take user with session Y's cookies for James's website and set my browser to use them. Now when I access James's site, James's site.
    	//
    	// Session Fixation is an attack technique that forces a user's session ID to an explicit value. Depending on the functionality of the target web site, a number of techniques can be utilized to "fix" the session ID value. These techniques range from Cross-site Scripting exploits to peppering the web site with previously made HTTP requests. After a user's session ID has been fixed, the attacker will wait for that user to login. Once the user does so, the attacker uses the predefined session ID value to assume the same online identity.
    	
    	if(session_status() === PHP_SESSION_ACTIVE){
    		session_regenerate_id(true); 
    		self::getLogger()->debug("Session id was regenerated: " . session_name() . " id: " . session_id());
    	}else{
    		die("regenerateId(): error, status is " . session_status());
    	}
    }
    
    /**
     * sets a specific value to a specific key of the session
     *
     * @param mixed $key key
     * @param mixed $value value
     */
    public static function set($key, $value){
        $_SESSION[$key] = $value;
    }

    /**
     * 
     *  A tutte le stringhe salvate in sessione applico il filtro  Filter::MyXSSFilter()
     *  Quindi per visualizzarle correttamente devo decodificarle con questo metodo
     * 
     * @param unknown $key
     * @return string
     */
    public static function getDecoded($key){    
    	return Filter::html_entity_invert(Session::get($key));
    }
    
    /**
     * gets/returns the value of a specific key of the session
     *
     * @param mixed $key Usually a string, right ?
     * @return mixed the key's value or nothing
     */
    public static function get($key){
    	$value = null;
        if (isset($_SESSION[$key])) {
        	$value = $_SESSION[$key];
			if (is_string($_SESSION[$key])) {
               
                // Filter the value for XSS vulnerabilities
				// Regola: "Make sure you escape on output, not on input", per questo non applico il filtro nel metodo Session::set() ma qui
				
				// Vedere:
				// http://stackoverflow.com/questions/14111659/securing-php-sessions-from-xss-attacks
				// https://www.owasp.org/index.php/XSS_%28Cross_Site_Scripting%29_Prevention_Cheat_Sheet
								
				if(false){					
					$value = Filter::XSSFilter($value); // Ho disabilitato questo controllo (presente nel codice di Panique) perchè causa problemi					
				}else{
					$value = Filter::MyXSSFilter($value); // questa è la mia soluzione che evita il loop di codifica sul carattere "&"
				}					
				
			}
			return $value;
		}
    }

    /**
     * adds a value as a new array element to the key.
     * useful for collecting error messages etc
     *
     * @param mixed $key
     * @param mixed $value
     */
    public static function add($key, $value){
        $_SESSION[$key][] = $value;
    }

    /**
     * deletes the session (= logs the user out)
     */
    public static function destroy(){
	
    	// session_status():
    	// PHP_SESSION_DISABLED if sessions are disabled.
    	// PHP_SESSION_NONE if sessions are enabled, but none exists.
    	// PHP_SESSION_ACTIVE if sessions are enabled, and one exists.
    		
	//if (session_id()) {
    if(session_status() === PHP_SESSION_ACTIVE){	
		self::getLogger()->debug("Destroying session: " . session_name() . " id: " . session_id());
		try {
	    	session_destroy();
		}catch (\Exception $e){
			$msg = "What causes this error ? This error is normally caused when PHP tries to delete the session file, but it can't find it. In your case with session_destroy there is only one place in PHP which causes this. That's when the session.save_handler (see as well  session_set_save_handler) returns FALSE for the destroy action. This can depends which type of save-handler you use, the default one is files. With that one, when the session.save_path setting is wrong (e.g. not an accessible directory), this would cause such an error.";
			throw new \Exception($msg);
		}
	}else{
		self::getLogger()->debug("No session to destroy");
	}
    }

    /**
     * update session id in database
     *
     * @access public
     * @static static method
     * @param  string $userName
     * @param  string $sessionId
     * @return string
     */
    public static function updateSessionId($userName, $sessionId){
    	$dql = "UPDATE " . UserModel::TABLE_NAME . " u SET u.sessionid = ";
    	if($sessionId){ // TOOD: migliorare lo stile del blocco seguente
    		$dql .= "'" . $sessionId . "'";
    	}else{
    		$dql .= "NULL";
    	}
    	$dql .= " WHERE u.username = '" . $userName . "'";
    	$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
    	return $numUpdated;
    }

    /**
     * checks for session concurrency
     *
     * This is done as the following:
     * UserA logs in with his session id('123') and it will be stored in the database.
     * Then, UserB logs in also using the same email and password of UserA from another PC,
     * and also store the session id('456') in the database
     *
     * Now, Whenever UserA performs any action,
     * You then check the session_id() against the last one stored in the database('456'),
     * If they don't match then log both of them out.
     *
     * @access public
     * @static static method
     * @return bool
     * @see Session::updateSessionId()
     * @see http://stackoverflow.com/questions/6126285/php-stop-concurrent-user-logins
     */
    public static function isConcurrentSessionExists(){
		$b=false;
		if(session_status() === PHP_SESSION_ACTIVE){
	        $session_id = session_id();
	        $userName   = Session::getDecoded(Session::SESSION_USER_NAME);
	
	        // self::getLogger()->debug("\$session_id : " . $session_id);
	        // self::getLogger()->debug("\$userName : " . $userName);
	        if(isset($userName) && isset($session_id)){
	        	$userSessionId = null;
	        	$dql =  "SELECT u FROM " . UserModel::TABLE_NAME . " u WHERE u.username = '" . $userName . "'";
	        	
				$result = DbResource::getEntityManager()->createQuery($dql)->getResult();
				// return one row (we only have one result or nothing)
				$user = array_shift($result);
	 
				if($user){
				//if(!empty($result)){ // Questo statement è un bug nel codice originale di PANIQUE (lasciare qui il commento)
	            	$userSessionId =  $user->getSessionid();
				}
				// self::getLogger()->debug("\$userSessionId : " . $userSessionId);
				if($userSessionId && $session_id !== $userSessionId){
	            	$b = true;
				}
	        }
		}
        // self::getLogger()->debug("isConcurrentSessionExists: " . $b);
        return $b;
    }

    /**
     * Checks if the user is logged in or not
     *
     * @return bool user's login status
     */
    public static function userIsLoggedIn(){
        return (Session::get(Session::SESSION_USER_LOGGED_IN) ? true : false);
    }
}