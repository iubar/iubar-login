<?php

namespace Iubar\Login\Controllers;

use Iubar\Login\Core\LoginAbstractController;
use Iubar\Login\Models\FacebookController as FacebookModel;
use Iubar\Login\Models\Login as LoginModel;
use Iubar\Login\Services\Session;


class FacebookController extends LoginAbstractController {
	
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
		$this->app->log->debug(get_class($this) . '->getLogin()');	
		$logged_in = LoginModel::isUserLoggedIn();
		$redirect = $this->getRedirectUrl();
		// Auto login
		if (!$logged_in) {			
			if (Session::getDecoded(Session::FB_ACCESS_TOKEN)) {
				// In questo caso posso evitare di visualizzare la form di login "server-side"
				// e provare a loggare direttamente l'utente
				$this->app->log->debug("Access token is in session, go directly to the callback route");
				$this->app->redirect($this->app->config('app.baseurl') .'/login/fb/callback' . '?redirect=' . urlencode($redirect));
			}else{
				$loginUrl = FacebookModel::getLoginUrl();
				$this->app->render($this->app->config('app.templates.path') . '/login/external/fb_login_server_side.twig', array(
						'redirect' => urlencode($redirect),
						'login_url' => $loginUrl,
						'feedback_positive' => $this->getFeedbackPositiveMessages(),
						'feedback_negative' => $this->getFeedbackNegativeMessages()
				));
			}
		}else{
		    $redirect_url = $this->app->config('auth.route.afterlogin');
		    if ($redirect){
		        $redirect_url .= '?redirect=' . urlencode($redirect);
		    }
		    $this->app->redirect($redirect_url);
		}
	}
	
	public static function getLoginCallback(){
		$this->app->log->debug(get_class($this) . '->getLoginCallback()');
		$accessToken = Session::getDecoded(Session::FB_ACCESS_TOKEN);
		if(!$accessToken){
			$accessToken = FacebookModel::getAccessTokenAfterLogin();
		}
		
		if($accessToken){
			$fbUser = FacebookModel::getUserFromGraphApi($accessToken);
			if($fbUser){
				echo 'Id: ' . $fbUser->getId();
				echo 'Display: ' . $fbUser->getName();
				echo 'Nome: ' . $fbUser->getFirstname();
				echo 'Cognome 1: ' . $fbUser->getMiddleName();
				echo 'Cognome 2: ' . $fbUser->getLastName();		
				echo 'Email: ' . $fbUser->getEmail();
				echo 'Picture: ' . $fbUser->getPicture()->getUrl();
				echo "<img src='" . $fbUser->getPicture()->getUrl() . "' />";
			}else{
				$fb_graph_user = FacebookModel::getUserFromGraphApi($accessToken);
				echo @r($fb_graph_user);
			}
		}
	}
	
	public function getLoginCallbackFromJs(){
		$this->app->log->debug(get_class($this) . '->getLoginCallbackFromJs()');
		$login_successful = false;
		if(Session::getDecoded(Session::FACEBOOK_ACCESS_TOKEN)){
			$login_successful = FacebookModel::loginWithAccesstoken();
		}else{
			$login_successful = FacebookModel::loginFromJs();
		}
		$this->redirectAfterLogin($login_successful);
	}
	
	public function getLoginButton(){
		$this->app->log->debug(get_class($this) . '->getLoginButton()');
		$this->app->render($this->app->config('app.templates.path') . '/login/external/fb_button.twig', array());
	}
	public function getLogout(){
		$this->app->log->debug(get_class($this) . '->getLogout()');
		$this->app->render($this->app->config('app.templates.path') . '/login/external/fb_logout.twig', array());
	}

}
