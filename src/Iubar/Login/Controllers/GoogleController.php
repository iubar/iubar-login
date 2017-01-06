<?php

namespace Iubar\Login\Controllers;

use Iubar\Login\Core\LoginAbstractController;
use Iubar\Login\Models\GoModel;
use Iubar\Login\Models\Login as LoginModel;
use Iubar\Login\Services\Session;

class GoogleController extends LoginAbstractController {
	
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
		$this->logger->debug(get_class($this) . '->getLogin()');
		$logged_in = LoginModel::isUserLoggedIn();
		// Auto login
		if (!$logged_in) {
			if (Session::getDecoded(Session::GOOGLE_JWT_TOKEN)) {
				// In questo caso posso evitare di visualizzare la form di login "server-side"
				// e provare a loggare direttamente l'utente
				// TODO: valutare i vantaggi di tale soluzione (rispetto al blocco "else" seguente)
				// prchè non sono stati verificati
				$this->logger->debug("Bearer token is in session, go directly to the callback route");
				$this->redirect($this->config('app.baseurl') .'/login/google/callback');
			}else{
				$redirect = $this->getRedirectUrl();
				$loginUrl = GoModel::getLoginUrl();
				$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.go-login-server-side'), array(
						'redirect' => urlencode($redirect),
						'login_url' => $loginUrl,
						'feedback_positive' => $this->getFeedbackPositiveMessages(),
						'feedback_negative' => $this->getFeedbackNegativeMessages()
				));
			}
		}else{
		    $redirect_url = $this->config('auth.routes.afterlogin');
		    if ($redirect){
		        $redirect_url .= '?redirect=' . urlencode($redirect);
		    }
		    $this->redirect($redirect_url);
		}
		
	}
	
	public function getLoginCustomButton(){
		$this->logger->debug(get_class($this) . '->getLoginCustomButton()');
		$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.go-custom-button'), array());
	}
	public function getLoginButton(){
		$this->logger->debug(get_class($this) . '->getLoginButton()');
		$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.go-button'), array());
	}
	public function getLogout(){
		$this->logger->debug(get_class($this) . '->getLogout()');
		$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.go-logout'), array());
	}

	public function getLoginCallback(){
		$this->logger->debug(get_class($this) . '->getLoginCallback()');
		$login_successful = false;
		if(isset($_REQUEST['code'])){
			$login_successful = GoModel::loginServerSide();
		} else if (isset($_SESSION[Session::GOOGLE_ACCESS_TOKEN])) {
			$login_successful = GoModel::loginServerSide();	
		}else if (isset($_REQUEST['bearer_token'])){
			$login_successful = GoModel::loginFromJs();
		}else if (isset($_SESSION[Session::GOOGLE_JWT_TOKEN])){
			$login_successful = GoModel::loginFromJs();
		}else{
			$this->logger->debug(get_class($this) . '->getLoginCallback(): situazione imprevista');
		}
		$this->redirectAfterLogin($login_successful);
	}
	 
}
