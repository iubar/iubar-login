<?php

namespace Iubar\Login\Models;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Models\User as UserModel;
use Iubar\Login\Models\AbstractLogin;

/**
 * Class UserRole
 *
 * This class contains everything that is related to up- and downgrading accounts.
 */
class UserRole extends AbstractLogin {

	const ONLY_READ = 1;
	const READ_WRITE = 2;
	const ADMIN = 7;
		
	/**
	 * Upgrades / downgrades the user's account. Currently it's just the field user_account_type in the database that
	 * can be 1 or 2 (maybe "basic" or "premium"). Put some more complex stuff in here, maybe a pay-process or whatever
	 * you like.
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public static function changeUserRole($type)
	{
		if (!$type) {
			return false;
		}
		// save new role to database
		if (self::saveRoleToDatabase($type)) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_ACCOUNT_TYPE_CHANGE_SUCCESSFUL'));
			return true;
		} else {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_TYPE_CHANGE_FAILED'));
			return false;
		}
	}
	/**
	 * Writes the new account type marker to the database and to the session
	 *
	 * @param $type
	 *
	 * @return bool
	 */
	public static function saveRoleToDatabase($type){
		// if $type is not 1 or 2
		if (!in_array($type, [1, 2])) {
			return false;
		}

		$dql = "UPDATE " . UserModel::TABLE_NAME . " u SET u.accounttype = '" . $type . "' WHERE u.username = '" . Session::getDecoded(Session::SESSION_USER_NAME) . "'";
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
 
		if ($numUpdated== 1) {
			// set account type in session
			Session::set(Session::SESSION_USER_ACCOUNT_TYPE, $type);
			return true;
		}
		return false;
	}
}