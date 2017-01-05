<?php

namespace Iubar\Login\Models;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Models\User;

/**
 * Handles all data manipulation of the admin part
 */
class Admin extends AbstractLogin {
	
	/**
	 * Sets the deletion and suspension values
	 *
	 * @param $suspensionInDays
	 * @param $softDelete
	 * @param $userName
	 */
	public static function setAccountSuspensionAndDeletionStatus($suspensionInDays, $softDelete, $userName){ 
		
		// Prevent to suspend or delete own account.
		// If admin suspend or delete own account will not be able to do any action.
		if ($userName == Session::get(Session.SESSION_USER_NAME)) {
			Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_CANT_DELETE_SUSPEND_OWN'));
			return false;
		}
						
		if ($suspensionInDays > 0) {
			$suspensionTime = time() + ($suspensionInDays * 60 * 60 * 24);
		} else {
			$suspensionTime = null;
		}
        // FYI "on" is what a checkbox delivers by default when submitted. Didn't know that for a long time :)
		if ($softDelete == "on") {
			$delete = 1;
		} else {
			$delete = 0;
		}				

		// write the above info to the database
		self::writeDeleteAndSuspensionInfoToDatabase($userName, $suspensionTime, $delete);

		// if suspension or deletion should happen, then also kick user out of the application instantly by resetting
		// the user's session :)
		if ($suspensionTime != null || $delete = 1) {
			self::resetUserSession($userName);
		}
		
	}
	
	
	/**
	 * Kicks the selected user out of the system instantly by resetting the user's session.
	 * This means, the user will be "logged out".
	 *
	 * @param $userName
	 * @return bool
	 */
	private static function resetUserSession($userName){
		$dql = "UPDATE " . User::TABLE_NAME . " u SET";
		$dql .=  " u.sessionid = NULL";
		$dql .=  " WHERE u.username = '" . $userName . "'";
		
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();

		if ($numUpdated == 1) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_ACCOUNT_USER_SUCCESSFULLY_KICKED'));
			return true;
		}
	}
		
	
	/**
	 * Simply write the deletion and suspension info for the user into the database, also puts feedback into session
	 *
	 * @param $userId
	 * @param $suspensionTime
	 * @param $delete
	 * @return bool
	 */
	private static function writeDeleteAndSuspensionInfoToDatabase($userName, $suspensionTime, $delete){
		
		$suspension_datetime = new \DateTime();
		$suspension_datetime->setTimestamp($suspensionTime);
		$datetime_string = $suspension_datetime->format('Y-m-d H:i:s');
		
		$dql = "UPDATE " . User::TABLE_NAME . " u SET";
		$dql .=  " u.suspensiontime = '" . $datetime_string . "',";
		$dql .=  " u.deleted = " . $delete;
		$dql .=  " WHERE u.username = '" . $userName . "'";
		
		$numUpdated = DbResource::getEntityManager()->createQuery($dql)->execute();
		
		if ($numUpdated == 1) {
			Session::add(Session::SESSION_FEEDBACK_POSITIVE, Text::get('FEEDBACK_ACCOUNT_SUSPENSION_DELETION_STATUS'));
			return true;
		}
		return false;
	}

 
}