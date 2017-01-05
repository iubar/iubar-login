<?php

namespace Iubar\Login\Models;
 
use Iubar\Login\Core\DbResource;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Models\User;
use Iubar\Login\Models\Registration;

class External extends AbstractLogin {

	const TABLE_NAME = 'Application\Models\Userexternal';

	public static function writeAccessTokenToDb($email, $access_token, $refreshToken, $scope, $expire_date, $provider_type){
		$dql = "UPDATE " . self::TABLE_NAME  . " u SET"
			. " u.accesstoken = '" . $access_token . "',"
			. " u.accesstokenscope = '" . $scope . "',"
			. " u.accesstokenexpireat = '" . $expire_date . "'"					
			. " u.refreshToken = '" . $refreshToken . "',"
			. " WHERE u.email= '" . $email . "'"
			. " AND u.providertype = '" . $provider_type . "'";
			$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
		return $numUpdated;
	}
	
	public static function registerNewUserDefault($user_id, $user_email, $provider_type){
		// Creo l'utente standard...;
		self::getLogger()->debug("Creo l'utente standard...");
		$b = Registration::registerNewUser($user_id, $user_email, null, null, null, null, $provider_type);
		if(!$b){
			self::getLogger()->debug("registerNewUserDefault() calls User::deleteUser()");
			User::deleteUser($user_email, $provider_type);
		}
		return $b;
	}
	
	public static function mergeAccount($external_id, $external_email, $provider_type){
		$b = false;
		if(!$external_id || !$external_email){
			throw new \InvalidArgumentException("mergeAccount(): invalid arguments");
		}
					
		$dql = "UPDATE " . User::TABLE_NAME . " u SET"
		. " u.username = '" . $external_id . "', "
		. " u.email = '" . $external_email . "', "
		. " u.activationhash = NULL, "
		. " u.pwdhash = NULL, "
		. " u.pwdresethash = NULL, "
		. " u.remembermetoken = NULL, "
		. " u.providertype = '" . $provider_type . "'"		
		. " WHERE u.email = '" . $external_email . "'"; 
		
		$numUpdated = 0;
		try{
			$em = DbResource::getEntityManager();
			$numUpdated = $em->createQuery($dql)->execute();
		} catch (\Exception $e) {
	    	// $em->getConnection()->rollback(); // SOLO SE uso transazioni: http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/transactions-and-concurrency.html 
	    	throw $e;
		}
		if ($numUpdated == 1) {
			if($provider_type==User::PROVIDER_TYPE_FB){
				Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_FB_USER_MERGED_SUCCESSFUL'));
				$b = true;
			}else if($provider_type==User::PROVIDER_TYPE_GO){
				Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_GO_USER_MERGED_SUCCESSFUL'));
				$b = true;
			}
		}
		
		if(!$b){
			if($provider_type==User::PROVIDER_TYPE_FB){
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_FB_USER_MERGED_FAILED'));	
			}else if($provider_type==User::PROVIDER_TYPE_GO){
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_GO_USER_MERGED_FAILED'));
			}else{
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_UNKNOWN_ERROR'));
			}
		}
		return $b;
	}
 
	public static function rollbackRegistrationByEmail($user_email, $provider_type){
		$dql = "DELETE FROM " . self::TABLE_NAME . " u WHERE u.email = '" . $user_email . "'"
		. " AND u.providertype = '" . $provider_type . "'";
		$numDeleted = DbResource::getEntityManager()->createQuery($dql)->execute();		
		return $numDeleted;
	}
	
	public static function rollbackRegistrationById($user_id){
		$dql = "DELETE FROM " . self::TABLE_NAME . " u WHERE u.id = '" . $user_id . "'";
		$numDeleted = DbResource::getEntityManager()->createQuery($dql)->execute();
		return $numDeleted;
	}	

	/**
	* return Application\Models\Userexternal
	*/	
	public static function getUserByAccessToken($accessToken, $provider_type){
		$result = DbResource::getEntityManager()->createQuery(
			"SELECT u FROM " . self::TABLE_NAME . " u WHERE u.accesstoken = '" . $accessToken . "'"
			. " AND u.providertype = '" . $provider_type . "'"	
		)->getResult();
		return array_shift($result);
	}
	
	/**
	* return Application\Models\Userexternal
	*/
	public static function getUserById($id){
		$result = DbResource::getEntityManager()->createQuery(
			"SELECT u FROM " . self::TABLE_NAME . " u WHERE u.id = '" . $id . "'"
		)->getResult();
		return array_shift($result);
	}
	
	/**
	* return Application\Models\Userexternal
	*/	
	public static function getUserByEmail($user_email, $provider_type){
		$result = DbResource::getEntityManager()->createQuery(
				"SELECT u FROM " . self::TABLE_NAME . " u WHERE u.email = '" . $user_email 
				. "' AND u.providertype = '" . $provider_type . "'"
				)->getResult();
				return array_shift($result);
	}	
	
}