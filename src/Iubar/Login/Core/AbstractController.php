<?php

namespace Iubar\Login\Core;

use Iubar\Login\Models\Login as LoginModel;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Csrf;

abstract class AbstractController {
	
	public $app = null;
	
	public function __construct(){
		$this->app = \Slim\Slim::getInstance();
	
		// always initialize a session
		Session::init();
	
		// check session concurrency
		$this->checkSessionConcurrency();
	
		// user is not logged in but has remember-me-cookie ? then try to login with cookie ("remember me" feature)
		if (!Session::userIsLoggedIn() && $this->app->getCookie('remember_me')) {
			$url = $this->app->config('app.baseurl') . '/login/loginWithCookie';
			if(!$this->routeIs($url)){
				$this->app->redirect($url);
			}
		}
	
	}
	
	public function routeIs($url){
		if ($url == $this->app->config('app.baseurl') . $this->app->request->getResourceUri()){
			return true;
		} else {
			return false;
		}
	}
	
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
	
		// initialize the session (if not initialized yet)
		Session::init();
	
		// $this->checkSessionConcurrency();
	
		// if user is not logged in
		if (!Session::userIsLoggedIn()) {
			// ... then treat user as "not logged in", destroy session, redirect to login page
			Session::destroy();
			// send the user to the login form page, but also add the current page's URI (the part after the base URL)
			// as a parameter argument, making it possible to send the user back to where he/she came from after a
			// successful login
	
			$this->app->redirect($this->app->config('app.baseurl') . '/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
			// in alterantiva a $_SERVER['REQUEST_URI'] forse si potrebbe uare $this->app->request()->getPath();
	
			// to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
			// the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
			// this is not optimal and will be fixed in future releases
			exit();
		}
	}
	
	/**
	 * The admin authentication flow, just check if the user is logged in (by looking into the session) AND has
	 * user role type 7 (currently there's only type 1 (normal user), type 2 (premium user) and 7 (admin)).
	 * If user is not, then he will be redirected to login page and the application is hard-stopped via exit().
	 * Using this method makes only sense in controllers that should only be used by admins.
	 */
	public function checkAdminAuthentication(){
	
		// initialize the session (if not initialized yet)
		Session::init();
	
		// $this->checkSessionConcurrency();
	
		// if user is not logged in or is not an admin (= not role type 7)
		if (!Session::userIsLoggedIn() || Session::get(Session::SESSION_USER_ACCOUNT_TYPE) != 7) {
			// ... then treat user as "not logged in", destroy session, redirect to login page
			Session::destroy();
	
			$this->redirectHome();
	
			// to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
			// the hard way, via exit(). @see https://github.com/panique/php-login/issues/453
			// this is not optimal and will be fixed in future releases
			exit();
		}
	}
	
	public function checkRoleTypeAuthentication($type=1){
	
		// initialize the session (if not initialized yet)
		Session::init();
	
		// $this->checkSessionConcurrency();
	
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
		$this->app->redirect($this->app->config('app.baseurl'));
	}
	
	public function redirectToLogin(){
		$url = '/login';
		$redirect = $this->getRedirectUrl();
		if($redirect){
			$url = '/login?redirect=' . urlencode($redirect);
		}
		$this->app->redirect($this->app->config('app.baseurl') . $url);
	}
	
	public function isAdmin(){
		$b = false;
		if (Session::userIsLoggedIn() && Session::get(Session::SESSION_USER_ACCOUNT_TYPE) == 7) {
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
			// $this->app->log->debug("userIsLoggedIn");
			if(Session::isConcurrentSessionExists()){
				// TODO: log something...
				LoginModel::logout();
				$this->redirectHome();
				exit();
			}
		}
	}
	
	public function getRedirectUrl(){
		$redirect = null;
		if($this->app->request->get('redirect')){
			$redirect = filter_var($redirect, FILTER_SANITIZE_URL); //This filter allows all letters, digits and $-_.+!*'(),{}|\\^~[]`"><#%;/?:@&=
			$redirect = ltrim(urldecode($this->app->request->get('redirect')));
		}
		return $redirect;
	}
	
	public function redirectAfterLogin($logged_in){
		if ($logged_in) {
			$this->redirectAfterSuccessfullyLogin();
		} else {
			$this->redirectAfterLoginError();
		}
	}
	
	private function redirectAfterLoginError(){
		$this->app->log->debug("Login failed");
		$this->redirectToLogin();
	}
	
	protected function renderLogin(){
		$redirect = $this->app->request->get('redirect');
		// FIXME: mockup hard-coded
		//$xml = "<xml><note><to>Tove</to><from>Jani</from><heading>Reminder</heading><body>Don't forget me this weekend!</body></note></xml>";
		//$redirect = "/api/fattura/import-directly/" . XmlUtil::base64url_encode($xml); // dimensione massima consigliata 64k
	
		$csrf_token = Csrf::makeToken(); // https://en.wikipedia.org/wiki/Cross-site_request_forgery
	
		$this->app->render('app/login/index.twig', array(
				'type' => 1,
				'captcha_key' => $this->app->config('captcha.key'),
				'redirect' => $redirect,
				'csrf_token' => $csrf_token,
				'feedback_positive' => $this->getFeedbackPositiveMessages(),
				'feedback_negative' => $this->getFeedbackNegativeMessages()
		));
	}
	
	protected function redirectAfterSuccessfullyLogin(){
		$app = $this->app;
		$redirect = $app->request->post('redirect');
		if (!$redirect){
			$redirect = $app->request->get('redirect');
		}
		if ($redirect) {
			$app->log->debug('login_successfully - redirecting to: ' . $redirect);
			$app->redirect($app->config('app.baseurl') . ltrim(urldecode($redirect)));
			// TODO: Verificare se lo statement precedente lavora correttamente sia con indirizzi relativi che assoluti
		} else {
			$app->log->debug('login_successfully - deafult redirecting to: ' . $app->config('auth.route.afterlogin'));
			$app->redirect($app->config('app.baseurl') . $app->config('auth.route.afterlogin'));
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