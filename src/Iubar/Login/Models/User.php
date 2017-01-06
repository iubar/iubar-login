<?php

namespace Iubar\Login\Models;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Models\Avatar as AvatarModel;
use Iubar\Login\Models\External as ExternalModel;
use Iubar\Login\Models\Registration;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Services\Filter;


class User extends AbstractLogin {
	
	// DEFAULT is the marker for "normal" accounts (that have a password etc.)
	// There are other types of accounts that don't have passwords etc. (FACEBOOK)
	const PROVIDER_TYPE_DEFAULT = 'DEFAULT';
	const PROVIDER_TYPE_FB = 'FACEBOOK';
	const PROVIDER_TYPE_GO = 'GOOGLE';
		
	const TABLE_NAME = 'Application\Models\User';
	const FILTER_CALLBACK_FUNC = '\Iubar\Login\Services\Filter::XSSFilter';
		
	public static function isAdmin(){
		$b = false;
		$user = self::getCurrentLogged();
		if ($user !== null){
			if ($user->getAccounttype() == UserRole::ADMIN){
				$b = true;
			}
		}		
		return $b;
	}
	
	public static function getAll(){
		return DbResource::getEntityManager()->createQuery("SELECT u FROM " . self::TABLE_NAME . " u")->getResult();
	}
	
// 	public static function getById($user_id){
// 		$utente = null;
// 		if ($id !== null){
// 			$utente = DbResource::getEntityManager()->find(self::TABLE_NAME, $user_id);
// 		}
// 		return $utente;
// 	}
	 
	public static function getByUsername($username){		 
		$result = DbResource::getEntityManager()->createQuery(
			"SELECT u FROM " . self::TABLE_NAME . " u WHERE u.username = '" . $username . "'"
		)->getResult();
		return array_shift($result);
		// oppure
		// $user = DbResource::getEntityManager()->find(self::TABLE_NAME, $username);
		// return $user;
	}
		
	/**
	 *
	 * @param string $email
	 * @return \Application\Models\Users
	 */
	public static function getByEmail($email){
		$result = DbResource::getEntityManager()->createQuery(
				"SELECT u FROM " . self::TABLE_NAME . " u WHERE u.email = '" . $email . "'"
		)->getResult();
		return array_shift($result);
	}
	
	public static function getUserDataByUserNameOrEmail($str){
		$user = self::getByUsername($str);
		if ($user === null){
			$user = self::getByEmail($str);
		}		
		return $user;
	}
		
 /**
     * Gets the user's data by user's id and a token (used by login-via-cookie process)
     *
     * @param $user_name
     * @param $token
     *
     * @return mixed Returns false if user does not exist, returns object with user's data when user exists
     */
    public static function getUserDataByUserNameAndToken($user_name, $token){

        // get real token from database (and all other data)
        $dql =  "SELECT u FROM " . self::TABLE_NAME . " u WHERE";                                   
        $dql .=  " u.username = '" . $user_name . "'";
        $dql .=  " AND u.remembermetoken = '" . $token . "'";     
        $dql .=  " AND u.remembermetoken IS NOT NULL";                  
        $dql .=  " AND u.providertype = '" . self::PROVIDER_TYPE_DEFAULT . "'";
        
        $result = DbResource::getEntityManager()->createQuery($dql)->getResult();
        // return one row (we only have one result or nothing)
        return array_shift($result);
    }

    /**
     * Gets an array that contains all the users in the database. The array's keys are the user ids.
     * Each array element is an object, containing a specific user's data.
     * The avatar line is built using Ternary Operators, have a look here for more:
     * @see http://davidwalsh.name/php-shorthand-if-else-ternary-operators
     *
     * @return array The profiles of all users
     */
    public static function getPublicProfilesOfAllUsers(){
		$all_users_profiles = array();
    	$dql = "SELECT u FROM " . self::TABLE_NAME . " u";
    	$users = DbResource::getEntityManager()->createQuery($dql)->getResult();        	
    
    	foreach ($users as $user) {
      
    		$userid = $user->getUsername();
    		$all_users_profiles[$userid] = new \stdClass(); 
//     		$all_users_profiles[$userid]->user_id = $userid;
    		$all_users_profiles[$userid]->user_name = $user->getUsername();
    		$all_users_profiles[$userid]->user_email = $user->getEmail();
    		$all_users_profiles[$userid]->user_active = $user->getActive();
    		$all_users_profiles[$userid]->user_deleted = $user->getDeleted();
    		$all_users_profiles[$userid]->user_avatar_link = self::getUserAvatarLink($user);
    		
     		if(self::isExternalAccount($user)){
    			// TODO: ... public profile for the Google or Facebook user
     		}

    		// all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
    		// Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
    		// the user's values
    		array_walk_recursive($all_users_profiles, self::FILTER_CALLBACK_FUNC);
			
    	}    
    	return $all_users_profiles;
    }
    
    /**
     * Gets a user's profile data, according to the given $user_name
     * @param string $user_name
     * @return mixed The selected user's profile
     */
    public static function getPublicProfileOfUser($user_name){
		$user_profile = null;
    	$user = self::getByUsername($user_name);    		
    	if ($user) {
			$user_profile['user_id']		= $user->getEmail();
    		$user_profile['user_name']		= $user->getUsername();			
    		$user_profile['user_email'] 	= $user->getEmail();
    		$user_profile['user_active'] 	= $user->getActive();
    		$user_profile['user_deleted'] 	= $user->getDeleted();
    		$user_profile['avatar_link'] 	= self::getUserAvatarLink($user);

			if(self::isExternalAccount($user)){
    			// TODO: ... public profile for the Google or Facebook user
     		}			

			// all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
			// Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
			// the user's values
			array_walk_recursive($user_profile, self::FILTER_CALLBACK_FUNC);
			
    	} else {
    		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
    	}
    
    	return $user_profile;
    }
    
    public static function getUserAvatarLink(Application\Models\User $user){
    	$user_avatar_link = null;
    	if (self::config('auth.gravatar.enabled')) {
    		$user_avatar_link = AvatarModel::getGravatarLinkByEmail($user->getEmail());
    	} else {
    		$user_avatar_link = AvatarModel::getPublicAvatarFilePathOfUser($user->getHasavatar(), $user->getUsername());
    	}
    	return $user_avatar_link;
    }
    
	public static function save(Application\Models\Userexternal $user){
		if ($user){
			$em = DbResource::getEntityManager();
			$em->persist($user);
			$em->flush();
		}
	}
    
    /**
     * Checks if a username is already taken
     *
     * @param $user_name string username
     *
     * @return bool
     */
    public static function doesUsernameAlreadyExist($user_name){
    	$user = self::getByUsername($user_name);
    	if (!$user) {
    		return false;
    	}
    	return true;
    }
    
    /**
     * Checks if a email is already used
     *
     * @param $user_email string email
     *
     * @return bool
     */
    public static function doesEmailAlreadyExist($user_email){    	
    	$user = self::getByEmail($user_email);
    	if (!$user) {
    		return false;
    	}
    	return true;
    }
    
    /**
     * Writes new username to database
     *
     * @param $user_id int user id
     * @param $new_user_name string new username
     *
     * @return bool
     */
    // Posso usare questo metodo solo s nel db orevedo la gestione del campo PK 'user_id'
//     public static function saveNewUserName($user_id, $new_user_name){
//     	$dql = "UPDATE " . self::TABLE_NAME . " u SET u.username = '" . $new_user_name . "' WHERE u.idanagrafica = " . $user_id;
//     	$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
//     	if ($numUpdated == 1) {
//     		return true;
//     	}
//     	return false;
//     }
    
    /**
     * Writes new email address to database
     *
     * @param $user_name string
     * @param $new_user_email string new email address
     *
     * @return bool
     */
    public static function saveNewEmailAddress($user_name, $new_user_email){
    	$dql = "UPDATE " . self::TABLE_NAME . " u SET u.email = '" . $new_user_email . "' WHERE u.username = '" . $user_name . "'";
    	$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
    	if ($numUpdated == 1) {
    		return true;
    	}
    	return false;
    }
    
    /**
     * Edit the user's name, provided in the editing form
     *
     * @param $new_user_name string The new username
     *
     * @return bool success status
     */
//     public static function editUserName($new_user_name)
//     {
//     	// new username same as old one ?
//     	if ($new_user_name == Session::get(Session::SESSION_USER_NAME)) {
//     		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_SAME_AS_OLD_ONE'));
//     		return false;
//     	}
    
    	// username cannot be empty and must be azAZ09 and 2-64 characters
//     	if (!preg_match("/^[a-zA-Z0-9]{2,64}$/", $new_user_name)) {
//     		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_DOES_NOT_FIT_PATTERN'));
//     		return false;
//     	}
    
//     	// clean the input, strip usernames longer than 64 chars (maybe fix this ?)
//     	$new_user_name = substr(strip_tags($new_user_name), 0, 64);
    
//     	// check if new username already exists
//     	if (self::doesUsernameAlreadyExist($new_user_name)) {
//     		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USERNAME_ALREADY_TAKEN'));
//     		return false;
//     	}
    
//     	return true;
//     }
    
    /**
     * Edit the user's email
     *
     * @param $new_user_email
     *
     * @return bool success status
     */
    public static function editUserEmail($new_user_email)
    {
    	// email provided ?
    	if (empty($new_user_email)) {
    		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_EMAIL_FIELD_EMPTY'));
    		return false;
    	}
    
    	// check if new email is same like the old one
    	if ($new_user_email == Session::getDecoded(Session::SESSION_USER_EMAIL)) {
    		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_EMAIL_SAME_AS_OLD_ONE'));
    		return false;
    	}
    
    	// user's email must be in valid email format, also checks the length
    	// @see http://stackoverflow.com/questions/21631366/php-filter-validate-email-max-length
    	// @see http://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address
    	if (!filter_var($new_user_email, FILTER_VALIDATE_EMAIL)) {
    		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_EMAIL_DOES_NOT_FIT_PATTERN'));
    		return false;
    	}
    
    	// strip tags, just to be sure
    	$new_user_email = substr(strip_tags($new_user_email), 0, 254);
    
    	// check if user's email already exists
    	if (self::doesEmailAlreadyExist($new_user_email)) {
    		Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_USER_EMAIL_ALREADY_TAKEN'));
    		return false;
    	}
    
    	// write to database, if successful ...
    	// ... then write new email to session, Gravatar too (as this relies to the user's email address)
    	if (self::saveNewEmailAddress(Session::getDecoded(Session::SESSION_USER_NAME), $new_user_email)) {
    		Session::set(Session::SESSION_USER_EMAIL, $new_user_email);
    		Session::set(Session::SESSION_USER_GRAVATAR_IMAGE_URL, AvatarModel::getGravatarLinkByEmail($new_user_email));
    		Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_EMAIL_CHANGE_SUCCESSFUL'));
    		return true;
    	}
    
    	Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_UNKNOWN_ERROR'));
    	return false;
    }
    
    public static function isExternalAccount(Application\Models\User $user){
    	$b = false;
		if($user){
			$provider_type = $user->getProvidertype();
			$b = self::isExternalProvider($provider_type);
		}
	    return $b;
	}
	
	public static function isExternalProvider($provider_type){
		$b = false;
		if($provider_type===self::PROVIDER_TYPE_FB){
			$b=true;
		}else if($provider_type===self::PROVIDER_TYPE_GO){
			$b=true;
		}
		return $b;
	}	

	public static function deleteUser($user_email, $provider_type){
		$b1 = true;
		$b2 = false;
	    if($provider_type){
			$b1 = ExternalModel::rollbackRegistrationByEmail($provider_type);
		}		
		$b2 = Registration::rollbackRegistrationByEmail($user_email);
		return $b1 && $b2;
	}
	
	public static function getCurrentLogged(){
		$user = null;		
		$username = Filter::html_entity_invert(Session::get(Session::SESSION_USER_NAME));		
		if ($username !== null){
			$user = self::getByUsername($username);
		}		
		return $user;
	}
	
	public static function isApiKeyAvailable($api_key){
		$b = false;
		$sql = "SELECT u ";
		$sql .= "FROM " . self::TABLE_NAME . " u ";
		$sql .= "WHERE u.apikey = '$api_key'";		
		$result = DbResource::getEntityManager()->createQuery($sql)->getOneOrNullResult();
		if ($result === null){
			$b = true;
		}		
		return $b;
	}
	
}