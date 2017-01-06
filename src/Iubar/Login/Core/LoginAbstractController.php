<?php

namespace Iubar\Login\Core;

use Iubar\Login\Models\User;
use Iubar\Login\Models\Login as LoginModel;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Csrf;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Models\UserRole;

abstract class LoginAbstractController extends \Iubar\Slim\Core\HtmlAbstractController {
	
	public static $route_after_login = '/welcome';
	
	protected $app = null;
	protected $logger = null;
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function __construct(){
		$this->app = AbstractLogin::getAppInstance();
		$this->logger = $this->app->log;
		
		// always initialize a session
		Session::init();
	
		// check session concurrency
		$this->checkSessionConcurrency();
	
		// user is not logged in but has remember-me-cookie ? then try to login with cookie ("remember me" feature)
		if (!Session::userIsLoggedIn() && $this->getCookie('remember_me')) {
			$url = $this->config('app.baseurl') . '/login/loginWithCookie';
			if(!$this->routeIs($url)){
				$this->redirect($url);
			}
		}
	}
	
	protected function getCookie($key){
		return $this->app->getCookie($key);
	}
	
	protected function render($twig, $data){
		$this->app->render($twig, $data);
	}
	
	protected function redirect($url){
		$this->app->redirect($url);
	}
	
	protected function config($key){
		return $this->app->config($key);
	}

	protected function params($key){
		return $this->app->request->params($key);
	}
	
	protected function post($key){
		return $this->app->request->post($key);
	}
	
	protected function get($key){
		return $this->app->request->get($key);
	}
	
	public function routeIs($url){
		if ($url == ($this->config('app.baseurl') . $this->app->request->getResourceUri())){
			return true;
		} else {
			return false;
		}
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * The normal authentication flow, just check if the user is logged in (by looking into the session).
	 * If user is not, then he will be redirected to login page and the application is hard-stopped via exit().
	 *
	 * Checks if user is logged in, if not then sends the user to "yourdomain.com/login".
	 * checkAuthentication() can be used in the constructor of a controller (to make the
	 * entire controller only visible for logged-in users) or inside a controller-method to make only this part of the
	 * application available for logged-in users.
	 */
	public function checkAuthentication(){
		
		// if user is not logged in
		if (!Session::userIsLoggedIn()) {
			// ... then treat user as "not logged in", destroy session, redirect to login page
			Session::destroy();
			// send the user to the login form page, but also add the current page's URI (the part after the base URL)
			// as a parameter argument, making it possible to send the user back to where he/she came from after a
			// successful login
	
			$this->redirect($this->config('app.baseurl') . '/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
			// in ambiente Slim, in alterantiva a $_SERVER['REQUEST_URI'] forse si potrebbe uare $this->request->getPath();
	
			// to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
			// the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
			// this is not optimal and will be fixed in future releases
			exit();
		}
	}
	
	/**
	 * The admin authentication flow, just check if the user is logged in (by looking into the session) AND has
	 * user role type 7. Currently there's only type 1 (normal user), type 2 (premium user) and 7 (admin).
	 * If user is not, then he will be redirected to login page and the application is hard-stopped via exit().
	 * Using this method makes only sense in controllers that should only be used by admins.
	 */
	public function checkAdminAuthentication(){
		
		// if user is not logged in or is not an admin
		if (!Session::userIsLoggedIn() || Session::get(Session::SESSION_USER_ACCOUNT_TYPE) != UserRole::ADMIN) {
			// ... then treat user as "not logged in", destroy session, redirect to login page
			Session::destroy();
	
			$this->redirectHome();
	
			// to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
			// the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
			// this is not optimal and will be fixed in future releases
			exit();
		}
	}
	
	public function checkRoleTypeAuthentication($type){
		
		// if user is not logged in or the account type is not $type
		if (!Session::userIsLoggedIn() || Session::get(Session::SESSION_USER_ACCOUNT_TYPE) != $type) {
			// ... then treat user as "not logged in", destroy session, redirect to login page
			Session::destroy();
	
			$this->redirectHome();
	
			// to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
			// the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
			// this is not optimal and will be fixed in future releases
			exit();
		}
	}
	
	public function redirectHome(){
		$this->redirect($this->config('app.baseurl'));
	}
	
	public function redirectToLogin(){
		$url = '/login';
		$redirect = $this->getRedirectUrl();
		if($redirect){
			$url = '/login?redirect=' . urlencode($redirect);
		}
		$this->redirect($this->config('app.baseurl') . $url);
	}
	
	public function isAdmin(){
		$b = false;
		if (Session::userIsLoggedIn() && Session::get(Session::SESSION_USER_ACCOUNT_TYPE) == UserRole::ADMIN) {
			$b = true;
		}
		return $b;
	}
	
	/**
	 * Detects if there is concurrent session (i.e. another user logged in with the same current user credentials),
	 * If so, then logout.
	 *
	 */
	public function checkSessionConcurrency(){
		if(Session::userIsLoggedIn()){
			// $this->logger->debug("userIsLoggedIn");
			if(Session::isConcurrentSessionExists()){
				// TODO: log something...
				LoginModel::logout();
				$this->redirectHome();
				exit();
			}
		}
	}
	
	public function redirectAfterLogin($logged_in){
		if ($logged_in) {
			$this->redirectAfterSuccessfullyLogin();
		} else {
			$this->redirectAfterLoginError();
		}
	}
	
	public function getRedirectUrl(){
	    $redirect = $this->get('redirect');
	    $redirect = $this->cleanUrl($redirect);
	    return $redirect;
	}
	
	private function cleanUrl($url){
	    if($url){
	        $url = filter_var($url, FILTER_SANITIZE_URL); //This filter allows all letters, digits and $-_.+!*'(),{}|\\^~[]`"><#%;/?:@&=
	        $url = ltrim(urldecode($url));
	    }
	    return $url;
	}
	
	private function redirectAfterLoginError(){
		$this->logger->debug("Login failed");
		$this->redirectToLogin();
	}
	
	protected function renderLogin(){
		$redirect = $this->getRedirectUrl();
	
		$csrf_token = Csrf::makeToken(); // https://en.wikipedia.org/wiki/Cross-site_request_forgery
	
		$this->render($this->config('app.templates.path') . '/' .  $this->config('auth.views.index'), array(
				'type' => 1,
				'captcha_key' => $this->config('auth.captcha.key'),
				'redirect' => urlencode($redirect),
				'csrf_token' => $csrf_token,
				'feedback_positive' => $this->getFeedbackPositiveMessages(),
				'feedback_negative' => $this->getFeedbackNegativeMessages()
		));
	}
	
	protected function redirectAfterSuccessfullyLogin(){
		$redirect = $this->cleanUrl($this->params('redirect'));
		if ($redirect) {
			$this->logger->debug('login_successfully - redirecting to: ' . $redirect);
			$this->redirect($this->config('app.baseurl') . $redirect);
			// TODO: Verificare se lo statement precedente lavora correttamente sia con indirizzi relativi che assoluti
		} else {
			$this->logger->debug('login_successfully - deafult redirecting to: ' . $this->config('auth.routes.afterlogin'));
			$this->redirect($this->config('app.baseurl') . $this->config('auth.routes.afterlogin'));
		}
	}
	
	/**
	 * renders the feedback messages into the view
	 */
	public function getFeedbackPositiveMessages(){
		// echo out the feedback messages (errors and success messages etc.),
		// they are in $_SESSION["feedback_positive"] and $_SESSION["feedback_negative"]
	
		// get the feedback (they are arrays, to make multiple positive/negative messages possible)
		$feedback_positive = Session::get(Session::SESSION_FEEDBACK_POSITIVE);
		// delete these messages (as they are not needed anymore and we want to avoid to show them twice
		Session::set(Session::SESSION_FEEDBACK_POSITIVE, null);
		return $feedback_positive;
	}
	
	/**
	 * renders the feedback messages into the view
	 */
	public function getFeedbackNegativeMessages(){
		// echo out the feedback messages (errors and success messages etc.),
		// they are in $_SESSION["feedback_positive"] and $_SESSION["feedback_negative"]
	
		// get the feedback (they are arrays, to make multiple positive/negative messages possible)
		$feedback_negative = Session::get(Session::SESSION_FEEDBACK_NEGATIVE);
		// delete these messages (as they are not needed anymore and we want to avoid to show them twice
		Session::set(Session::SESSION_FEEDBACK_NEGATIVE, null);
		return $feedback_negative;
	}
	
}