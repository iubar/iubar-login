<?php

namespace Iubar\Login\Controllers;

use Iubar\Login\Core\AbstractController;
use Iubar\Login\Models\Login;
use Iubar\Login\Models\Registration;
use Iubar\Login\Models\User;
use Iubar\Login\Services\Encryption;

class Register extends AbstractController {
	
	/**
	 * Construct this object by extending the basic Controller class
	 */
	public function __construct(){
		parent::__construct();
	}
	
	public function getRegister(){
		$this->app->log->debug(get_class($this).'->getRegister()');
		if (Login::isUserLoggedIn()) {
			$this->app->redirect(AbstractController::$route_after_login);
		} else {

			$data = array();
			$data['type'] = 2;
			$data['captcha_key'] = $this->app->config('captcha.key');
			$data['feedback_positive'] = $this->getFeedbackPositiveMessages();
			$data['feedback_negative'] = $this->getFeedbackNegativeMessages();
			
			$redirect = $this->app->request->get('redirect');
			if ($redirect){
				$data['redirect'] = $redirect;
			}
			
			$this->app->render('app/login/index.twig', $data);
		}
	}
	
	/**
	 * Register page action
	 * POST-request after form submit
	 */
	public function postRegister(){
	
		$this->app->log->debug(get_class($this).'->postRegister()');
	
		// clean the input
		$user_email = strip_tags($this->app->request->post('user_email'));
		$user_name = null; // strip_tags($this->app->request->post('user_name'));
		if(!$user_name){ // Se non specificato, utilizzo l'indirizzo email come username
			$user_name = $user_email;
		}
		$user_email_repeat = NULL; // potrei usare strip_tags($this->app->request->post('user_email_repeat'));
		$user_password_new = $this->app->request->post('user_password_new');
		$user_password_repeat = $this->app->request->post('user_password_repeat');
		$captcha = $this->app->request->post('g-recaptcha-response');
		$redirect = $this->app->request->post('redirect');
		
		$registration_successful = Registration::registerNewUser(
			$user_name, 
			$user_email,
			$user_email_repeat,
			$user_password_new, 
			$user_password_repeat, 
			$captcha,
			UserModel::PROVIDER_TYPE_DEFAULT);
		
		$redirect_url = null;
		if ($registration_successful) {
			
			$login_successful = Login::login($user_name, $user_password_new, true, UserModel::PROVIDER_TYPE_DEFAULT);
			
			if($login_successful){
				$redirect_url = AbstractController::$route_after_login;
				if ($redirect){
					$redirect_url = $this->app->config('app.baseurl') .'/login?redirect=' . $redirect;
				}	
			}
			
		} else {
			$redirect_url = $this->app->config('app.baseurl') . '/register';
			
			if ($redirect){
				$redirect_url .= '?redirect=' . $redirect;
			}
		}
		
		$this->app->redirect($redirect_url);
	}
	

	/**
	 * Verify user after activation mail link opened
	 * @param string $ua_verification_code user's activation verification token
	 */
	public function getVerify($ua_verification_code)
	{
		$this->app->log->debug(get_class($this).'->getVerify()');
		$user_name = Encryption::decrypt($this->app->request->get("user_name"));
		if (isset($user_name) && isset($ua_verification_code)) {			
			$success = Registration::verifyNewUser($user_name, $ua_verification_code);
			if($success){
				// TODO: valutare se inviare mail di benvenuto all'utente
			}
			$this->app->render('app/login/verify.twig', array(
				'feedback_positive' => $this->getFeedbackPositiveMessages(), 
				'feedback_negative' => $this->getFeedbackNegativeMessages()					
			));
		} else {
			$this->app->redirect($this->app->config('app.baseurl') .'/login');
		}
	}

	
}
