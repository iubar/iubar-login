<?php

namespace Application\Controllers\Login;

use Application\Core\LoginAbstractController;
use Application\Models\Login\GoModel;
use Application\Models\Login\UserModel;
use Application\Models\Login\LoginModel;
use Application\Services\Session;
use Application\Services\Login\Csrf;


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
		$this->app->log->debug(get_class($this) . '->getLogin()');
		$logged_in = LoginModel::isUserLoggedIn();
		// Auto login
		if (!$logged_in) {
			if (Session::getDecoded(Session::GOOGLE_BEARER_TOKEN)) {
				// In questo caso posso evitare di visualizzare la form di login "server-side"
				// e provare a loggare direttamente l'utente
				// TODO: valutare i vantaggi di tale soluzione (rispetto al blocco "else" seguente)
				// prchè non sono stati verificati
				$this->app->log->debug("Bearer token is in session, go directly to the callback route");
				$this->app->redirect($this->app->config('app.baseurl') .'/login/google/callback'); // TODO: it's hard-coded
			}else{
				$redirect = $this->getRedirectUrl();
				$loginUrl = GoModel::getLoginUrl();
				$this->app->render($this->app->config('app.templates.path') . '/login/external/go_login_server_side.twig', array(
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
	
	public function getLoginCustomButton(){
		$this->app->log->debug(get_class($this) . '->getLoginCustomButton()');
		$this->app->render($this->app->config('app.templates.path') . '/login/external/go_custom_button.twig', array());
	}
	public function getLoginButton(){
		$this->app->log->debug(get_class($this) . '->getLoginButton()');
		$this->app->render($this->app->config('app.templates.path') . '/login/external/go_button.twig', array());
	}
	public function getLogout(){
		$this->app->log->debug(get_class($this) . '->getLogout()');
		$this->app->render($this->app->config('app.templates.path') . '/login/external/go_logout.twig', array());
	}

	public function getLoginCallback(){
		$this->app->log->debug(get_class($this) . '->getLoginCallback()');
		$login_successful = false;
		if(isset($_REQUEST['code'])){
			$login_successful = GoModel::loginServerSide();
		}else if (isset($_REQUEST['bearer_token'])){
			$login_successful = GoModel::loginFromJs();
		}else if (Session::getDecoded(Session::GOOGLE_BEARER_TOKEN)){
			$login_successful = GoModel::loginFromJs();
		}
		$this->redirectAfterLogin($login_successful);
	}
	 
}
