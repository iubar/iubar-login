<?php

namespace Iubar\Login\Models;

use Application\Models\Userexternal;
use Application\Models\User;

use Iubar\Login\Core\DbResource;
use Iubar\Login\Services\Session;
use Iubar\Login\Services\Text;
use Iubar\Login\Models\External;
use Iubar\Login\Models\User as UserModel;
use Iubar\Login\Models\Login;
use Iubar\Login\Models\AbstractLogin;
use Iubar\Login\Interfaces\IExternalModel;

use Facebook\Facebook as FacebookSdk;
use Facebook\FacebookResponse;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Authentication\AccessToken;

class Facebook extends AbstractLogin implements IExternalModel {
	
	// https://developers.facebook.com/docs/graph-api/reference/v2.5/user
	// https://developers.facebook.com/docs/php/api/5.0.0

	// https://www.facebook.com/settings?tab=applications
	
	const FORCE_TOKEN_REFRESH_AFTER_LOGIN = false;
	
	private static $fb = null;
	
	private static function getFb(){
		if(self::$fb == null){
			self::$fb = new FacebookSdk([
					'app_id' => self::config('auth.facebook.appid'),
					'app_secret' => self::config('auth.facebook.appsecret'),
					'default_graph_version' => self::config('auth.facebook.apiver')
					// Once you have an access token stored in a PHP session or in your database, 
					// you can set it as the default fallback access token in the constructor 
					// of the Facebook\Facebook() service class. 
					// The default access token will be used as a fallback access token 
					// if one is not provided for a specific request.					
					// 'default_access_token' => '{default-access-token}'
			]);
		}
		return self::$fb;
	}

	// Il seguente metodo è utilizzato solo se
	// si è scelto di non utilizzare l'SDK JS
	// ovvero solo per il flow server-side	
	public static function getLoginUrl(){
		$fb = self::getFb();
		$helper = $fb->getRedirectLoginHelper();
		$permissions = ['public_profile, email']; // optional
// 		The FacebookRedirectLoginHelper makes use of sessions
// 		to store a CSRF value. You need to make sure you have sessions enabled
// 		before invoking the getLoginUrl()		
		$loginUrl = $helper->getLoginUrl('http://' . $_SERVER['HTTP_HOST'] . '/login/fb/callback', $permissions);
		return $loginUrl;		
	}
	
	// Il seguente metodo è utilizzato solo se
	// si è scelto di non utilizzare l'SDK JS
	// ovvero solo per il flow server-side
	public static function getAccessTokenAfterLogin(){
		$accessToken = null;
		$fb = self::getFb();
		$helper = $fb->getRedirectLoginHelper();
		try {
			$accessToken = $helper->getAccessToken();
		} catch(FacebookResponseException $e) {
			// When Graph returns an error
			$msg = 'Graph returned an error: ' . $e->getMessage();
			throw new \RuntimeException($msg);
			//exit;
		} catch(FacebookSDKException $e) {
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		
		if (! isset($accessToken)) {
			echo 'No cookie set or no OAuth data could be obtained from cookie.';
			if ($helper->getError()) {
				header('HTTP/1.0 401 Unauthorized');
				echo "Error: " . $helper->getError() . "\n";
				echo "Error Code: " . $helper->getErrorCode() . "\n";
				echo "Error Reason: " . $helper->getErrorReason() . "\n";
				echo "Error Description: " . $helper->getErrorDescription() . "\n";
			} else {
				header('HTTP/1.0 400 Bad Request');
				echo 'Bad request';
			}
			exit;
			
		}else{
			
			// Logged in FB
		    // Store the $accessToken in a PHP session
		    // You can also set the user as "logged in" at this point

// 			There are three login states of a user on Facebook:			
// 				1) Not logged into Facebook
// 				2) Logged into Facebook but have not authorized your app
// 				3) Logged into Facebook and have authorized your app			
			

			self::getLogger()->debug('access token string: ' . $accessToken->getValue());
			
			if(!$accessToken->isLongLived()){
				$accessToken = self::getExtendAccessToken($accessToken); // returns a Long Lived Access Token
				// User is logged in with a long-lived access token.
			}else{
				// it's already an long-lived access token
			}
						
			Session::set(Session::FACEBOOK_ACCESS_TOKEN, (string) $accessToken); // nota il cast obbligatorio, poichè $accessToken è un oggetto 
		
			// Now you can redirect to another page and use the
			// access token from $_SESSION['facebook_access_token']			
			// You can redirect the user to a members-only page.	
		}
		return $accessToken;
	}
	
	private static function getAccessTokenFromJsSdk($extend_lifetime=false){
		// It obtains data from js cookie
		
		// Obtaining an access token from the SDK for JavaScript		
		// If you're already using the Facebook SDK for JavaScript to authenticate users, 
		// you can obtain the access token with PHP by using the FacebookJavaScriptHelper.
		$accessToken = null;
		$fb = self::getFb();
		
		// Grabbing User Data From A Signed Request
		// JavaScriptHelper is used to obtain an access token 
		// or signed request from the cookie set by the JavaScript SDK.			
		// The cookie that the JavaScript SDK created contains a signed request.
		// A signed request contains a payload of data about the user who authorized your app. The payload is delivered as a base64-encoded JSON string that has been escaped for use in a URL. It also contains a signature hash to validate against to ensure the data is coming from Facebook.
		// The cookie is named fbsr_{your-app-id}		
		$helper = $fb->getJavaScriptHelper(); // return an instance of FacebookJavaScriptHelper		
		
		$fb_id = $helper->getUserId();
		if ($fb_id) {
			// User is logged in
			// TODO: Ma sono sicuro che l'app è autorizzata ????
		}
		// TODO: Forse potrei subito interrogare il db per verificare se il token è in tabella e recuperare l'utente		
		// 		se fb_id è presente nel db
		// 			recupero l'ultimo access token
		// 				se è scaduto
		// 					lo aggiorno
		// 				skip
		
		$signedRequest = $helper->getSignedRequest();		
		if ($signedRequest) {
			$payload = $signedRequest->getPayload();
			self::getLogger()->debug('Payload: ' . @rt($payload));
		}

		try {				
		  $accessToken = $helper->getAccessToken(); // Returns an AccessToken entity from the signed request.
		} catch(FacebookResponseException $e) {
		  // When Graph returns an error
			$msg = 'Graph returned an error: ' . $e->getMessage();
			throw new \RuntimeException($msg);			
		  //exit;
		} catch(FacebookSDKException $e) {
		  // When validation fails or other local issues
		  $msg = 'Facebook SDK returned an error: ' . $e->getMessage();
			throw new \RuntimeException($msg);		  
		}

		if (! isset($accessToken)) {
			// Unable to read JavaScript SDK cookie
			echo 'No cookie set or no OAuth data could be obtained from cookie.';
			exit;
		}else{
			// Logged in
    		// Store the $accessToken in a PHP session or in your database, you can set it as the default fallback access token
			// You can set it as the default fallback access token in the constructor of the Facebook\Facebook() service class.
			// Alternatively if you already have an instance of Facebook\Facebook(), you can set the default fallback access token using the setDefaultAccessToken() method.
			
			
    		// You can also set the user as "logged in" at this point
			self::getLogger()->debug('access token string: ' . $accessToken->getValue());
				
			if($extend_lifetime){
				if(!$accessToken->isLongLived()){
					$accessToken = self::getExtendAccessToken($accessToken); // returns a Long Lived Access Token
					// User is logged in with a long-lived access token.
				}
			}
			
			Session::set(Session::FACEBOOK_ACCESS_TOKEN, (string) $accessToken);
			
		}
		return $accessToken;
	}
	

	public static function getUserFromGraphApi($accessToken){
		// Attenzione:  OAuth 2.0 Spec stated that
		// "authorization codes must be short lived and SINGLE use"
	
		$fb = self::getFb();
		// Using the Graph API and the Facebook SDK
		try {
				
			// We grab the id, name and email of the logged in user. This assumes we've already logged the user in with FB.login().			
			// If you provided a 'default_access_token', the '{access-token}' is optional.			
			// Returns a `Facebook\FacebookResponse` object
			//$response = $fb->get('/me?fields=id,name,email,picture,first_name,middle_name,last_name', $accessToken);
			$response = $fb->get('/me?fields=id,name,email,picture,first_name,middle_name,last_name', $accessToken);
			// The /me endpoint is a special endpoint that refers to the User or Page
			// that is making the request.
			// If you use a user access token to make a GET request to /me,
			// Graph will return a User node. If you use a page access token to make
			// a GET request to /me, a Page node will be returned.
	
		} catch(FacebookResponseException $e) {
			// When Graph returns an error
			$msg = 'Graph returned an error: ' . $e->getMessage();
			throw new \RuntimeException($msg);
			// exit;
		} catch(FacebookSDKException $e) {
			// When validation fails or other local issues
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		
		self::getLogger()->debug('Raw response: ' . @rt($response));
		$fb_graph_user = $response->getGraphUser();
		// per comodità potrei anche usare $plainOldArray = $response->getDecodedBody();
		return $fb_graph_user;
	}
	
// Example	
// 	public static function getPostContent($access_token){	
// 		$fb = Facebook::getFb();
// 		// https://developers.facebook.com/docs/graph-api/reference/v2.5/
// 		$linkData = [
// 				'link' => 'http://www.example.com',
// 				'message' => 'User provided message',
// 		];
// 		try {
// 			$response = $fb->post('/me/feed', $linkData, $access_token);
// 		} catch(FacebookResponseException $e) {
// 			$msg = 'Graph returned an error: ' . $e->getMessage();
// 			throw new \RuntimeException($msg);
// 			// exit;
// 		} catch(FacebookSDKException $e) {
// 			echo 'Facebook SDK returned an error: ' . $e->getMessage();
// 			exit;
// 		}
		
// 		$graphNode = $response->getGraphNode();
		
// 		echo 'Posted with id: ' . $graphNode['id'];
// 		return $graphNode;
// 	}

	private static function getExtendAccessToken($accessToken){
		$longLivedAccessToken = null;
		$fb = self::getFb();
		// When a user first logs into your app, the access token your app receives will be a short-lived access token that lasts about 2 hours. It's generally a good idea to exchange the short-lived access token for a long-lived access token that lasts about 60 days.
		
		// Extending the access token
		// To extend an access token, you can make use of the OAuth2Client.
		
		// The OAuth 2.0 client handler helps us manage access tokens
		$oAuth2Client = $fb->getOAuth2Client();
				
		if (! $accessToken->isLongLived()) {
			try {
				// Exchanges a short-lived access token for a long-lived one
				$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			} catch (FacebookSDKException $e) {
				echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
				exit;
			}

			self::getLogger()->debug('\$longLivedAccessToken: ' . $longLivedAccessToken->getValue());
		}else{
			self::getLogger()->debug('No need to request new token. The actual token will expire at: ' . self::getExpireDateAsString($accessToken) . '');
			$longLivedAccessToken = $accessToken;
		}
		return $longLivedAccessToken;
	}

	private static function registerNewUserExternal($fb_graph_user, $accessToken){

			$fb_id = $fb_graph_user->getId();
			
			if(External::getUserById($fb_id) !== null) {
				self::getLogger()->debug('Fb user\'s id aleady in use');
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_FB_ID_ALREADY_TAKEN'));
				return false;
			}
					
			if(External::getUserByEmail($fb_id, UserModel::PROVIDER_TYPE_FB) !== null) {
				self::getLogger()->debug('Fb user\'s id aleady in use');
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_FB_EMAIL_ALREADY_TAKEN'));
				return false;
			}
			
			// write user data to database
			if (!self::writeNewFbUserToDatabase($fb_graph_user, $accessToken)) {
				self::getLogger()->debug('Registrazione fallita');
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_CREATION_FAILED'));
				return false;
			}									
			return true;				
	}
	
	private static function registerOrMergeNewUserDefault($fb_graph_user){
		$user = null;
		if($fb_graph_user){
			$fb_email = $fb_graph_user->getEmail();
			$user = UserModel::getByEmail($fb_email);
			if($user){ // Allora esiste già un utente con la stessa email
				// Merge dell'account esistente con i dati FB
				//
				// Nota che le situazioni critiche sono due
				// 1) L'utente esiste già nella tabella User ma non in quella UserExternal
				// 2) L'utente esiste già in entrambe le tabelle ma con 'provider' differente
				self::getLogger()->debug("registerOrMergeNewUserDefault(): calling self::mergeAccount()");
				$b = self::mergeAccount($user, $fb_graph_user);
							
			}else{
				// Creo l'utente standard...;
				$fb_id = $fb_graph_user->getId();
				self::getLogger()->debug("registerOrMergeNewUserDefault(): calling External::registerNewUserDefault()");
				$b = External::registerNewUserDefault($fb_id, $fb_email, UserModel::PROVIDER_TYPE_FB);
			}
		}else{
			// error
		}
		return $b;
	}

	private static function loginFromJs2($fb_graph_user, $accessToken){
		$fb_email = $fb_graph_user->getEmail();
		self::getLogger()->debug("Calling Login::loginExternal()");
		$login_successful = Login::loginExternal($fb_email, UserModel::PROVIDER_TYPE_FB);
		// check login status: if true, then redirect user to user/index, if false, then to login form again
		if ($login_successful) {
		
			self::getLogger()->debug("Login successfully");
		
			if(self::FORCE_TOKEN_REFRESH_AFTER_LOGIN){
				// Scambio l'access token con uno a lunga durata, lo salvo nel db e aggiorno l'oggeto Facebook.
				self::getLogger()->debug("Exchange access short live token '" . $accessToken . "' for a long live one");
				$accessToken = self::getExtendAccessToken($accessToken); // ask FB for a long-lived token
				self::getLogger()->debug("New log live token: '" . $accessToken . "'");
				Session::set(Session::FACEBOOK_ACCESS_TOKEN, (string) $accessToken); // qui il cast è obbligatorio perchè $accessToken è un oggetto
			}
		
			$scope = null; 			// TODO:
			$expire_date = null; 	// TODO:
			
			External::writeAccessTokenToDb($fb_email, $accessToken, null, $scope, $expire_date, UserModel::PROVIDER_TYPE_FB);
						
			self::getFb()->setDefaultAccessToken($accessToken);
		
			$fb_id =  $fb_graph_user->getId();
			$fb_display = $fb_graph_user->getName();
			$fb_pic_url = $fb_graph_user->getPicture()->getUrl();
		
			Session::set(Session::FACEBOOK_DISPLAY_NAME, $fb_display);
			Session::set(Session::FACEBOOK_PICTURE, $fb_pic_url);
			Session::set(Session::FACEBOOK_ID, $fb_id);
		}
		return $login_successful;
	}


	private static function getFbUserOrRegister($fb_graph_user, $accessToken){
		$fb_id = $fb_graph_user->getId();
		$fb_user = External::getUserById($fb_id);
		if(!$fb_user){
			self::getLogger()->debug("Calling self::registerNewUserExternal()");
			$b2 = self::registerNewUserExternal($fb_graph_user, $accessToken);
			if(!$b2){
				External::rollbackRegistrationById($fb_id); 
				throw new \RuntimeException("Can't register the Fb user");
			}
			$fb_user = External::getUserById($fb_id);
		}else{
			// questa è una situazione che si verifica solo se, in precedenza,
			// la procedura di creazione utente non è andata a buon fine
			self::getLogger()->debug("Fb user already exists");
		}
		return $fb_user;
	}
	
	private static function getUserOrRegister($fb_graph_user){
		$fb_id = $fb_graph_user->getId();
		$user = UserModel::getByUsername($fb_id);
		if(!$user){
			$continue = self::registerOrMergeNewUserDefault($fb_graph_user);
			if($continue){
				// After the creation I fetch the user from the db
				$user = UserModel::getByUsername($fb_id);
			}
		}
		return $user;
	}
	
	// LOGIN
	// 			There are three login states of a user on Facebook:
	// 				1) Not logged into Facebook
	// 				2) Logged into Facebook but have not authorized your app
	// 				3) Logged into Facebook and have authorized your app
	//			And in our web-app we have two states:
	//				1) Logged in
	//				2) Not logged in	
	public static function loginFromJs(){
		$login_successful = false;
		
		$accessToken = self::getAccessTokenFromJsSdk(false);
		
		if(Session::getDecoded(Session::FACEBOOK_ACCESS_TOKEN)){
			$msg = "Access token in session: " . Session::getDecoded(Session::FACEBOOK_ACCESS_TOKEN);
			$msg2 = "Access token from request: " . $accessToken;
			self::getLogger()->debug($msg);
			self::getLogger()->debug($msg2);
		}
		
		$login_successful = self::loginWithAccessToken2($accessToken);
		return $login_successful;
	}
	
	private static function loginWithAccessToken2($accessToken){
		$login_successful = false;
		// echo "Access token in in cookie: " . $accessToken . "<br/>";
		if($accessToken){
			$fb_graph_user = null;
			$fb_user = null;
			$user = null;
		
			if(!$accessToken->isExpired()){
					
				// Grab the user id and any other user data, e.g. name & email from the Graph API using the user access token.
				$fb_graph_user = self::getUserFromGraphApi($accessToken);
				$fb_id = $fb_graph_user->getId();
		
				// Per maggior sicurezza:
				if(self::validateToken($accessToken, $fb_id)){
		
					$fb_user = self::getFbUserOrRegister($fb_graph_user, $accessToken);
					if($fb_user){
						$user = self::getUserOrRegister($fb_graph_user);
						if(!$user){
							self::getLogger()->debug("\$user is null");
							throw new \RuntimeException("\$user is null");
						}
					}else{
						self::getLogger()->debug("\$fb_user is null");
						throw new \RuntimeException("\$fb_user is null");
						// Nota: a volte getFbUserOrRegister() potrebbe ritornare null ma il record dell'utente nella tabella UserExternal è stato comunque inserito
						// Occorre quindi verificare il contenuto della tabella
					}
				}else{
					self::getLogger()->debug("token non valido per utente " . $fb_id);
					throw new \RuntimeException("token non valido per utente " . $fb_id);
				}
		
			}else{
				self::getLogger()->debug("Access token scaduto");
			}
		
			if($user && $fb_user){
				$login_successful = self::loginFromJs2($fb_graph_user, $accessToken);
			}else{
				throw new \RuntimeException("Errore imprevisto");
			}
		
		}else{
			echo "Errore nel recuperare l'access token";
			exit;
		}
		return $login_successful;
	}
		
	public static function writeNewFbUserToDatabase($fb_graph_user, $accessToken){
		if($fb_graph_user){

			$fb_id = $fb_graph_user->getId(); 
			$email = $fb_graph_user->getEmail();
			$display = $fb_graph_user->getName();
			$first_name = $fb_graph_user->getFirstName();
			$middle_name = $fb_graph_user->getMiddleName();
			$last_name = $fb_graph_user->getLastName();
			$pic_url = $fb_graph_user->getPicture()->getUrl();
			$now = new \DateTime();
			$now->setTimestamp(time());				
			$ip = self::getRequestIp();
			 			
			$fbUser = new Userexternal();
			$fbUser->setId($fb_id);
			$fbUser->setDisplay($display);
			$fbUser->setEmail($email);
			$fbUser->setFirstName($first_name);
			$fbUser->setMiddleName($middle_name);
			$fbUser->setLastName($last_name);		
			$fbUser->setPictureUrl($pic_url);
			$fbUser->setCreationtime($now);
			$fbUser->setCreationip($ip);
			$fbUser->setAccesstoken($accessToken); 	// TODO: la scrittura va ripetuta ogni volta che effettuo il refresh del token
													// TODO: insieme ad accessToken devo scrivere 
													// $fbUser->setAccesstokenexpireat()
													// $fbUser->setAccesstokenscope()
			$fbUser->setProvidertype(UserModel::PROVIDER_TYPE_FB);
			try {
				$em = DbResource::getEntityManager();			
				$em->persist($fbUser);
				$em->flush();
				return true;
			} catch (\Exception $e) {
				return false;
			}
		}
	}
	
	private static function validateToken($accessToken, $userId=null){
		$fb = self::getFb();
		// The OAuth 2.0 client handler helps us manage access tokens
		$oAuth2Client = $fb->getOAuth2Client();		
		// Get the access token metadata
		$tokenMetadata = $oAuth2Client->debugToken($accessToken); // Get the metadata associated with the access token.
		// echo '<h3>Metadata</h3>';
		self::getLogger()->debug('\$tokenMetadata: ' . @rt($tokenMetadata));
		try{
			$tokenMetadata->validateAppId(self::config('auth.facebook.appid'));  // Nota: il metodo non ritorna nulla, solo un'eccezione se la validazione fallisce
			if($userId){
				$tokenMetadata->validateUserId($userId); // Nota: il metodo non ritorna nulla, solo un'eccezione se la validazione fallisce
			}
			$tokenMetadata->validateExpiration();  // Nota: il metodo non ritorna nulla, solo un'eccezione se la validazione fallisce
		} catch (FacebookSDKException $e) {
			// Probabile tentativo di hackergaggio;
			echo "<p>Error validating the token: " . $helper->getMessage() . "</p>\n\n";
			exit;
		}
		return true;
	}
	
	private static function getExpireDateAsString($accessToken){
		$timeString = "";
		if($accessToken){
			try {
				// Get info about the token
				// Returns a GraphSessionInfo object
				$isExpired = $accessToken->isExpired();
				$expiresAt = $accessToken->getExpiresAt();
				if($expiresAt){
					$timeString = $expiresAt->format('Y-m-d H:i:s');
				}
			} catch(FacebookSDKException $e) {
				echo 'Error getting access token info: ' . $e->getMessage();
				exit;
			}
		
		}
		return $timeString; 
	}
	
	private static function mergeAccount(User $user, $fb_graph_user){
		$b = false;
		if(!$user || !$fb_graph_user){
			throw new \InvalidArgumentException("mergeAccount(): invalid arguments");
		}
		$email = $user->getEmail();
		$fbEmail = $fb_graph_user->getEmail();
		if($email == $fbEmail){
			$fbId = $fb_graph_user->getId();
			$b = External::mergeAccount($fbId, $fbEmail, UserModel::PROVIDER_TYPE_FB);
		}
		return $b;
	}
	 	
	public static function loginWithAccessToken(){
		$access_token_string = Session::getDecoded(Session::FACEBOOK_ACCESS_TOKEN);
		$accessToken = new AccessToken($access_token_string);
		return self::loginWithAccessToken2($accessToken);		
	}
	
	public static function dateTimeFromTimestamp($unix_timestamp){
		$date = null;
		$date = new \DateTime();
		$date->setTimestamp($unix_timestamp);
		return $date;
	}
	
	public static function stringToDateTime($string, $format='Y-m-d H:i:s'){
		$date = \DateTime::createFromFormat($format, $string);
		return $date;
	}
}
