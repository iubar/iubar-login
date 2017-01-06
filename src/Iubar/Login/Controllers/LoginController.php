<?php

namespace Iubar\Login\Controllers;

use Iubar\Login\Core\LoginAbstractController;
use Iubar\Login\Models\PasswordReset as PasswordResetModel;
use Iubar\Login\Models\Login as LoginModel;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Csrf;
use Iubar\Login\Models\User as Usermodel;
use Iubar\Login\Services\Encryption;

class LoginController extends LoginAbstractController {

	/**
	 * Construct this object by extending the basic Controller class
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	* Index, default action (shows the login form), when you do login/index
	*/
	public function getLogin(){
		$this->logger->debug(get_class($this).'->getLogin()');
		// if user is logged in redirect to main-page, if not show the view
		$logged_in = LoginModel::isUserLoggedIn();
		if($logged_in){
			$this->logger->debug("Already logged-in, redirecting...");			 
			$this->redirectAfterSuccessfullyLogin();
		}else{
			$this->renderLogin();
		}
	}
			
	/**
	 * The login action, when you do login/login
	 */
	public function postLogin(){ // Il metodo è utilizzato solo per il login con email
		$this->logger->debug(get_class($this).'->postLogin()');
		
		// check if csrf token is valid
		$token = $this->post(Session::SESSION_CSRF_TOKEN);
		if (!Csrf::isTokenValid($token)) {
			LoginModel::logout();
			$this->redirectHome();
			exit();
		}
		
		// perform the login method, put result (true or false) into $login_successful
		$login_successful = LoginModel::login(
			$this->post('user_name'), 
			$this->post('user_password'), 
			$this->post('set_remember_me_cookie'),
			UserModel::PROVIDER_TYPE_DEFAULT
		);
		
		// check login status: if true, then redirect user to user/index, if false, then to login form again
		$this->redirectAfterLogin($login_successful);
	}
	
	/**
	 * The logout action
	 * Perform logout, redirect user to main-page
	 */
	public function getLogout(){
		$this->logger->debug(get_class($this).'->getLogout()');
		LoginModel::logout();
		$this->redirectHome();
		exit();
	}
	
	/**
	 * Login with cookie
	 */
	public function getLoginWithCookie(){
		$this->logger->debug(get_class($this).'->getLoginWithCookie()');
		// run the loginWithCookie() method in the login-model, put the result in $login_successful (true or false)
		$login_successful = LoginModel::loginWithCookie($this->getCookie('remember_me'));
		// if login successful, redirect to dashboard/index ...
		if ($login_successful) {
			$this->redirectAfterSuccessfullyLogin();
		} else {
			// if not, delete cookie (outdated? attack?) and route user to login form to prevent infinite login loops
			LoginModel::deleteCookie();
			$this->redirectToLogin();
		}
	}
	
	/**
	 * Show the request-password-reset page
	 */
	public function getRequestPasswordReset(){
		$this->logger->debug(get_class($this).'->getRequestPasswordReset()');
		$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.request-password-reset'), array(
			'captcha_key' => $this->config('auth.captcha.key'),
			'feedback_positive' => $this->getFeedbackPositiveMessages(),
			'feedback_negative' => $this->getFeedbackNegativeMessages()				
		));
	}
	
	/**
	 * The request-password-reset action
	 * POST-request after form submit
	 */
	public function postRequestPasswordReset(){
		$this->logger->debug(get_class($this).'->postRequestPasswordReset()');
		$user_name_or_email = strip_tags($this->post('user_name_or_email'));
		$captcha = $this->post('g-recaptcha-response');
		PasswordResetModel::requestPasswordReset(
				$user_name_or_email,
				$captcha);
		$this->redirect($this->config('app.baseurl') .'/login/password-dimenticata');
		// ...oppure $this->redirectToLogin(); // TODO: commentare le differenze
		
	}
	
	/**
	 * Verify the verification token of that user (to show the user the password editing view or not)
	 * @param string $user_name username
	 * @param string $verification_code password reset verification token
	 */
	public function getVerifyPasswordReset($verification_code){
		$this->logger->debug(get_class($this).'->getVerifyPasswordReset()');
		// check if this the provided verification code fits the user's verification code
		$user_name = Encryption::decrypt($this->get("user_name"));
		if (PasswordResetModel::verifyPasswordReset($user_name, $verification_code)) {
			
			$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.password-reset'), array(
				'user_name' => $user_name,
				'user_password_reset_hash' => $verification_code,
				'feedback_positive' => $this->getFeedbackPositiveMessages(),
				'feedback_negative' => $this->getFeedbackNegativeMessages()
			));	
		} else {
			$this->redirectToLogin();
		}
	}
	
	/**
	 * Set the new password
	 * Please note that this happens while the user is not logged in. The user identifies via the data provided by the
	 * password reset link from the email, automatically filled into the <form> fields. See verifyPasswordReset()
	 * for more. Then (regardless of result) route user to index page (user will get success/error via feedback message)
	 * POST request !
	 * TODO this is an _action
	 */
	public function postNewPassword(){
		$this->logger->debug(get_class($this).'->postNewPassword()');
		PasswordResetModel::setNewPassword(
				$this->post('user_name'), $this->post('user_password_reset_hash'),
				$this->post('user_password_new'), $this->post('user_password_repeat')
				);
		$this->redirectToLogin();
	}

}
