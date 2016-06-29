<?php

namespace Iubar\Login\Services;

/**
 * Cross Site Request Forgery Class
 *
 */
/**
 * Instructions:
 *
 * At your form, before the submit button put:
 * <input type="hidden" name="csrf_token" value="<?= Csrf::makeToken(); ?>" />
 *
 * This validation needed in the controller action method to validate CSRF token submitted with the form:
 * if (!Csrf::isTokenValid($token)) {
 *      LoginModel::logout();
 *    	$this->redirectHome();
 *      exit();
 *  }
 * And that's all
 */
class Csrf {
    /**
     * get CSRF token and generate a new one if expired
     *
     * @access public
     * @static static method
     * @return string
     */
    public static function makeToken() {
        $max_time    = 60 * 60 * 24; // token is valid for 1 day
        $stored_time = Session::get(Session::SESSION_CSRF_TIME);
        $csrf_token  = Session::get(Session::SESSION_CSRF_TOKEN);
        if($max_time + $stored_time <= time() || empty($csrf_token)){
            Session::set(Session::SESSION_CSRF_TOKEN, md5(uniqid(rand(), true)));
            Session::set(Session::SESSION_CSRF_TIME, time());
        }
        return Session::get(Session::SESSION_CSRF_TOKEN);
    }
    /**
     * checks if CSRF token in session is same as in the form submitted
     *
     * @access public
     * @static static method
     * @param $token
     * @return bool
     */
        public static function isTokenValid($token){        
        return $token === Session::get(Session::SESSION_CSRF_TOKEN) && !empty($token);
    }
}