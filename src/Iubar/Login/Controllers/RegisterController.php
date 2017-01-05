<?php

namespace Iubar\Login\Controllers;

use Iubar\Login\Core\LoginAbstractController;
use Iubar\Login\Models\Login as LoginModel;
use Iubar\Login\Models\Registration as RegistrationModel;
use Iubar\Login\Models\User as UserModel;
use Iubar\Login\Services\Encryption;

class RegisterController extends LoginAbstractController {
	
	/**
	 * Construct this object by extending the basic Controller class
	 */
	public function __construct(){
		parent::__construct();
	}
	
	public function getRegister(){
		$this->logger->debug(get_class($this).'->getRegister()');
		if (LoginModel::isUserLoggedIn()) {
			$this->redirect(LoginAbstractController::$route_after_login);
		} else {

			$data = array();
			$data['type'] = 2;
			$data['captcha_key'] = $this->config('captcha.key');
			$data['feedback_positive'] = $this->getFeedbackPositiveMessages();
			$data['feedback_negative'] = $this->getFeedbackNegativeMessages();
			
			$redirect = $this->getRedirectUrl();
			if ($redirect){
				$data['redirect'] = urlencode($redirect);
			}
			
			$this->render($this->config('app.templates.path') . '/login/index.twig', $data);
		}
	}
	
	/**
	 * Register page action
	 * POST-request after form submit
	 */
	public function postRegister(){
	
		$this->logger->debug(get_class($this).'->postRegister()');
	
		// clean the input
		$user_email = strip_tags($this->post('user_email'));
		$user_name = null; // strip_tags($this->post('user_name'));
		if(!$user_name){ // Se non specificato, utilizzo l'indirizzo email come username
			$user_name = $user_email;
		}
		$user_email_repeat = NULL; // potrei usare strip_tags($this->post('user_email_repeat'));
		$user_password_new = $this->post('user_password_new');
		$user_password_repeat = $this->post('user_password_repeat');
		$captcha = $this->post('g-recaptcha-response');
		$redirect = ltrim(urldecode($this->post('redirect')));
		
		$registration_successful = RegistrationModel::registerNewUser(
			$user_name, 
			$user_email,
			$user_email_repeat,
			$user_password_new, 
			$user_password_repeat, 
			$captcha,
			UserModel::PROVIDER_TYPE_DEFAULT);
		

		$redirect_url = null;
		if ($registration_successful) {
			
			$login_successful = LoginModel::login($user_name, $user_password_new, true, UserModel::PROVIDER_TYPE_DEFAULT);
			
			if($login_successful){
				$redirect_url = $this->config('auth.route.afterlogin');
					$redirect_url = $this->config('app.baseurl') .'/login?redirect=' . urlencode($redirect);
				}	
			
		} else {
			$redirect_url = $this->config('app.baseurl') . '/register';
			if ($redirect){
		      $redirect_url .= '?redirect=' . urlencode($redirect);
			}
		}
		
		$this->redirect($redirect_url);

    }
	

	/**
	 * Verify user after activation mail link opened
	 * @param string $ua_verification_code user's activation verification token
	 */
	public function getVerify($ua_verification_code)
	{
		$this->logger->debug(get_class($this).'->getVerify()');
		$user_name = Encryption::decrypt($this->get("user_name"));
		if (isset($user_name) && isset($ua_verification_code)) {			
			$success = RegistrationModel::verifyNewUser($user_name, $ua_verification_code);
			if($success){
				// TODO: valutare se inviare mail di benvenuto all'utente
			}
			$this->render($this->config('app.templates.path') . '/login/verify.twig', array(
				'feedback_positive' => $this->getFeedbackPositiveMessages(), 
				'feedback_negative' => $this->getFeedbackNegativeMessages()					
			));
		} else {
			$this->redirect($this->config('app.baseurl') .'/login');
		}
	}

	
}
