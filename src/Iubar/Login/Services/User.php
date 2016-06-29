<?php

namespace Iubar\Login\Services;

use Application\Core\DbResource;
use Iubar\Login\Services\Filter;

class User {
	
	const TABLE_NAME = 'Application\Models\User';
	const ONLY_READ = 1;
	const READ_WRITE = 2;
	const COMMERCIALISTA = 5;
	const ADMIN = 7;

	public static function getAll(){
		return DbResource::getEntityManager()->createQuery("SELECT a FROM " . self::TABLE_NAME . " a")->getResult();
	}
	
	public static function save(\Application\Models\User $user = null){
		if ($user !== null){
			$em = \Application\Core\DbResource::getEntityManager();
			$em->persist($user);
			$em->flush();
		}
	}
	
	/**
	 *
	 * @param int $username
	 * @return \Application\Models\User
	 */
	public static function getByUsername($username){
		$user = null;
		if ($username !== null){
			$user = DbResource::getEntityManager()->find(self::TABLE_NAME, $username);
		}
		return $user;
	}
	
	/**
	 * 
	 * @return \Application\Models\User
	 */
	public static function getCurrentLogged(){
		$user = null;
		
		$username = Filter::html_entity_invert(
			\Application\Services\Session::get(\Application\Services\Session::SESSION_USER_NAME)
		);
		
		if ($username !== null){
			$user = self::getByUsername($username);
		}
		
		return $user;
	}
	
	public static function isAdmin(){
		$b = false;
		$user = self::getCurrentLogged();
		if ($user !== null){
			if ($user->getAccounttype() == 7){
				$b = true;
			}
		}
		
		return $b;
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