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

// Messaggio per commit
// https://github.com/google/google-api-php-client/blob/a4cd92fbb56f8e80503765cd582a319ec9c6855b/UPGRADING.md

/**
* 
* Google Project Creation
* 
* Go to the Google Developers Console (https://console.developers.google.com/)
* Select an existing project, or create a new project by clicking Create Project:
* In the Project name field, type in a name for your new project.
* In the Project ID field, the console has created project ID. Optionally you can type in a project ID for your project. But project ID must be unique worldwide.
* Click on the Create button and the project to be created in some seconds. Once the project is created successfully, the project name would be appearing at the top of the left sidebar.
* In the left sidebar, select APIs under the APIs & auth section. A list of Google APIs appears.
* Find the Google+ API service and set its status to Enable.
* In the sidebar, select Credentials under the APIs & auth section.
* In the OAuth section of the page, select Create New Client ID.
* Create Client ID dialog box would be appearing for choosing application type.
* In the Application type section of the dialog, select Web application and click on the Configure consent screen button.
* Choose Email address, enter the Product name and save the form.
* In the Authorized JavaScript origins field, enter your app origin. If you want to allow your app to run on different protocols, domains, or subdomains, then you can enter multiple origins.
* In the Authorized redirect URI field, enter the redirect URL.
* Click on Create Client ID.
* 
*/
class GoModel extends AbstractLogin implements IExternalModel {
	
	// https://developers.google.com/identity/sign-in/web/people
	
	// https://security.google.com/settings/security/permissions
	
	// https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=
	// https://www.googleapis.com/oauth2/v3/tokeninfo
	
	// https://console.developers.google.com/
	
	// From https://auth0.com/blog/2014/01/27/ten-things-you-should-know-about-tokens-and-cookies/
	// The main difference between these tokens and the ones we've been discussing is that signed tokens (e.g.: JWT) are "stateless". They don't need to be stored on a hash-table, hence it's a more lightweight approach. OAuth2 does not dictate the format of the access_token so you could return a JWT from the authorization server containing the scope/permissions and the expiration.	
	
	// The OAuth 2.0 Authorization Framework: Bearer Token Usage
	// https://tools.ietf.org/html/rfc6750
	
	const TABLE_NAME = 'Application\Models\Userexternal';
	private static $client_secrets_file =  "../config/google-client-secret.json"; // JSON can be generated in the Credentials section of Google Developer Console.
	private static $go_client = null;
	
	public static function getGoClient(){
		
		if(!is_readable(self::$client_secrets_file)){
			throw new \RuntimeException("Can not load the json file: " . self::$client_secrets_file);
		}
		
		if(GoModel::$go_client == null){
			GoModel::$go_client = new \Google_Client();
			GoModel::$go_client->setLogger(self::getLogger());
			
			// $client->setDeveloperKey('INSERT HERE');
			
			GoModel::$go_client->setAuthConfig(self::$client_secrets_file);
			// or...
			// putenv('GOOGLE_APPLICATION_CREDENTIALS=/path/to/google-client-secret.json');
			// GoModel::$go_client->useApplicationDefaultCredentials();
			//
			
			// In order to impersonate a user, call setSubject() when your service account credentials are being used
			// $user_to_impersonate = 'user@example.org';
			// $client->setSubject($user_to_impersonate);

			GoModel::$go_client->addScope(\Google_Service_Oauth2::PLUS_LOGIN);
			GoModel::$go_client->addScope(\Google_Service_Oauth2::PLUS_ME);
			GoModel::$go_client->addScope(\Google_Service_Oauth2::USERINFO_PROFILE);
			GoModel::$go_client->addScope(\Google_Service_Oauth2::USERINFO_EMAIL);
			// When access_type=online you are also allowed to specify a value for approval_prompt.
			// If it is set to approval_prompt=force, your user will always be prompted, 
			// even if they have already granted.			
			// On the other hand, when access_type=offline, approval_prompt can only be set to approval_prompt=force,
			// but to make up for this restriction you're also provided a refresh_token which 
			// you can use to refresh your access token.
			GoModel::$go_client->setAccessType("online");	// default: offline 
															// see: https://developers.google.com/api-client-library/php/auth/web-app
			GoModel::$go_client->setApprovalPrompt("auto");
			GoModel::$go_client->setRedirectUri(self::config('app.baseurl'). '/login/google/callback');
		}
		return GoModel::$go_client;
	}

	public static function storeAccessToken($accessToken, $refreshToken, $email){
		// Store the access-token and refresh-token (into the session + into the db)
		Session::set(Session::GOOGLE_ACCESS_TOKEN, $accessToken);
		Session::set(Session::GOOGLE_REFRESH_TOKEN, $refreshToken);
		$scope = null; // TODO: ...
		$expire_time = $accessToken['created'] + $accessToken['expires_in'];	
		// Using a UNIX timestamp.  Notice the result is in the UTC time zone.
		$expire_date = new \DateTime('@' . $expire_time);
		$expire_str = $expire_date->format('Y-m-d H:i:s');
		External::writeAccessTokenToDb($email, $accessToken, $refreshToken, $scope, $expire_str, UserModel::PROVIDER_TYPE_GO);		
	}
	
	private static function loginServerSide2(){
		$client = GoModel::getGoClient();
					
		////////////////// Google_Service_Oauth2			
		$user_info = null;
		$google_oauth2  = new \Google_Service_Oauth2($client);
		if($google_oauth2){
			echo "<div>User info</div>";
			//$userinfo_v2_me = $google_oauth2->userinfo_v2_me->get();
			$user_info = $google_oauth2->userinfo->get();
			echo @rt($user_info); // html out format
		
			echo $user_info->name."<br>";
			echo $user_info->id."<br>";
			echo $user_info->email."<br>";
			echo $user_info->link."<br>";
			echo $user_info->picture."<br>";
		}

		////////////////// Google_Service_Plus
		$google_plus = new \Google_Service_Plus($client);
		if($google_plus){
			echo "<div>googlePlus</div>";
			$user_profile = $google_plus->people->get('me');
			echo @rt($user_profile); // html out format
		}
			
		// Arrivati qui l'access token non può essere già scaduto
		if ($client->isAccessTokenExpired()) {
				echo "access token expired: <div>" . @rt($accessToken) . "</div>" . PHP_EOL;
		}
		
		// TODO: completare, ovvero effttuare registrazione / login dell'utente
		die("TODO: ....");

		/*
		$userData = l'array deve essere costruito con i valori di $user_profile e $user_info
		
		$go_user = self::getGoUserOrRegister($userData);
					
		if($go_user){
			$user = self::getUserOrRegister($userData);
			if(!$user){
				self::getLogger()->debug("\$user is null");
				throw new \RuntimeException("\$user is null");
			}
		}else{
			self::getLogger()->debug("\$go_user is null");
			throw new \RuntimeException("\$go_user is null");
		}
		
		if($user && $go_user){
			$login_successful = self::loginFromJs2($userData);
		}else{
			throw new \RuntimeException("Errore imprevisto");
		}
					
		*/
		
		
		// ATTENZIONE: Per verificare se l'utente è loggato usare:
 		//		if ($client->isAccessTokenExpired()) {			
		//			$accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
		//			$refreshToken = $client->getRefreshToken();
		//			if(!$refreshToken){
		//				die('Situazione imprevista');
		//				// se si vedrifica questo errore, significa che non bisogna invocare di nuovo getRefreshToken() (vedi sopra l'if)
		//			}
		//			Session::set(Session::GOOGLE_ACCESS_TOKEN, $accessToken);
		//			Session::set(Session::GOOGLE_REFRESH_TOKEN, $refreshToken);
		//			$email = ....
		//			GoModel::storeAccessToken($accessToken, $refreshToken, $email);
		// 		}
		
	}
	
	public static function getLoginUrl(){
		$client = self::getGoClient();
		$auth_url = $client->createAuthUrl();
		return $auth_url;
	}

	private static function loginFromJs2(array $userData){
		
		if(empty($userData)){
			throw new \InvalidArgumentException ("\$userData is null or empty");
		}
		
		$go_email = $userData["email"];
		self::getLogger()->debug("Calling Login::loginExternal()");
		$login_successful = Login::loginExternal($go_email, UserModel::PROVIDER_TYPE_GO);
		// check login status: if true, then redirect user to user/index, if false, then to login form again
		if ($login_successful) {
		
			self::getLogger()->debug("Login successfully");
			$go_id = $userData['sub'];
			
			if(isset($userData["name"])){
				$go_display = $userData["name"];
				Session::set(Session::GOOGLE_DISPLAY_NAME, $go_display);
			}
			if(isset($userData["picture"])){
				$go_pic_url = $userData["picture"];
				Session::set(Session::GOOGLE_PICTURE, $go_pic_url);
			}
		
			Session::set(Session::GOOGLE_ID, $go_id);

		}
		return $login_successful;
	}
	
	private static function getGoUserOrRegister(array $userData){
		
		if(empty($userData)){
			throw new \InvalidArgumentException ("\$userData is null or empty");
		}
		
		$go_id = $userData['sub']; 
		$go_user = External::getUserById($go_id);
		if(!$go_user){
			self::getLogger()->debug("Calling self::registerNewUserExternal()");
			$b2 = self::registerNewUserExternal($userData);
			if(!$b2){
				External::rollbackRegistrationById($go_id);
				throw new \RuntimeException("Can't register the Google user");
			}
			$go_user = External::getUserById($go_id);
		}else{
			// questa è una situazione che si verifica solo se, in precedenza,
			// la procedura di creazione utente non è andata a buon fine
			self::getLogger()->debug("Fb user already exists");
		}
		return $go_user;
	}
		
	private static function getUserOrRegister(array $userData){
		
		if(empty($userData)){
			throw new \InvalidArgumentException ("\$userData is null or empty");
		}
		
		$go_id = $userData['sub'];
		$user = UserModel::getByUsername($go_id);
		if(!$user){
			$continue = self::registerOrMergeNewUserDefault($userData);
			if($continue){
				// After the creation I fetch the user from the db
				$user = UserModel::getByUsername($go_id);
			}
		}
		return $user;
	}
	
	private static function registerOrMergeNewUserDefault(array $userData){
		
		if(empty($userData)){
			throw new \InvalidArgumentException ("\$userData is null or empty");
		}
		
		$go_email = $userData["email"];
		$user = UserModel::getByEmail($go_email);
		if($user){ // Allora esiste già un utente con la stessa email
			// Merge dell'account esistente con i dati Google
			//
			// Nota che le situazioni critiche sono due
			// 1) L'utente esiste già nella tabella User ma non in quella UserExternal
			// 2) L'utente esiste già in entrambe le tabelle ma con 'provider' differente
			self::getLogger()->debug("registerOrMergeNewUserDefault(): calling self::mergeAccount()");
			$b = self::mergeAccount($user, $userData);
						
		}else{
			// Creo l'utente standard...;
			$go_id = $userData['sub'];
			self::getLogger()->debug("registerOrMergeNewUserDefault(): calling External::registerNewUserDefault()");
			$b = External::registerNewUserDefault($go_id, $go_email, UserModel::PROVIDER_TYPE_GO);
		}
	return $b;
	}
		
	private static function mergeAccount(User $user, array $userData){
		$b = false;
		if(!$user || !empty($userData)){
			throw new \InvalidArgumentException("mergeAccount(): invalid arguments");
		}
		$email = $user->getEmail();
		$go_email = $userData["email"]; 
		if($email == $go_email){
			$go_id = $userData['sub'];
			$b = External::mergeAccount($go_id, $go_email, UserModel::PROVIDER_TYPE_GO);
		}		
		return $b;		
	}
	
	private static function registerNewUserExternal(array $userData){
		
			if(empty($userData)){
				throw new \InvalidArgumentException ("\$userData is null or empty");
			}
			
			$go_id = $userData['sub'];
			
			if(External::getUserById($go_id) !== null) {
				self::getLogger()->debug('Fb user\'s id aleady in use');
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_GO_ID_ALREADY_TAKEN'));
				return false;
			}
					
			if(External::getUserByEmail($go_id, UserModel::PROVIDER_TYPE_GO) !== null) {
				self::getLogger()->debug('Google user\'s id aleady in use');
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_GO_EMAIL_ALREADY_TAKEN'));
				return false;
			}
			
			// write user data to database
			if (!self::writeNewGoUserToDatabase($userData)) {
				self::getLogger()->debug('Registrazione fallita');
				Session::add(Session::SESSION_FEEDBACK_NEGATIVE, Text::get('FEEDBACK_ACCOUNT_CREATION_FAILED'));
				return false;
			}									
			
			return true;
	}
	
	/**
	* Authenticate with a backend server
	* @see https://developers.google.com/identity/sign-in/web/backend-auth
	*/
	public static function loginFromJs(){

	    self::getLogger()->debug("loginFromJs");
	    
		$login_successful = false;
		$client = GoModel::getGoClient();
	
		// Chiarimento:
		// $expired = $client->isAccessTokenExpired(); 
		// Nota che qui $expired vale sempre TRUE, mentre $has_expired qui è sempre FALSE !!!
		// Ovviamente perchè siamo in presenza di un "flow" di tipo Bearer	
		// Using the client side flow (ie Java Script) you can only receive a JWT token (not an access-token)						
		// Vedi: https://developers.google.com/identity/sign-in/web/backend-auth
		
		$id_token = null;
		if(isset($_REQUEST['id_token'])){	
			$id_token = $_REQUEST['id_token'];
			if($id_token){
			 Session::set(Session::GOOGLE_JWT_TOKEN, $id_token);
		  }
		}
		
		if(Session::getDecoded(Session::GOOGLE_JWT_TOKEN)){
			$id_token2 = Session::getDecoded(Session::GOOGLE_JWT_TOKEN);
			$msg = "Bearer token in session: " . $id_token2;
			$msg2 = "Bearer token from request: " . $id_token;
			self::getLogger()->debug($msg);
			self::getLogger()->debug($msg2);
			$id_token = $id_token2;
		}
		
		self::getLogger()->debug("loginFromJs ...");
		
		if($id_token){

			
	// 		echo "<div> id_token ";
	// 		@rt($id_token);
	// 		echo "</div>";
				
			$token_array = json_decode($id_token, true);
				
			$scope = null;
			if(isset($token_array["scope"])){
				$scope = $token_array["scope"];
			}

			$has_expired=false;
			
			if(false){ // Il metodo verifyIdToken(), invocato successivamente, effettua già i controlli seguenti
				if(isset($token_array["expires_at"])){ // TODO: verificare che non ci sia un errore di sintassi e che sia "expires_in"
					
					$expires_at = $token_array["expires_at"];
					$first_issued_at = $token_array["first_issued_at"];
					
					// Using a UNIX timestamp.
					$date_expire = self::stringToDateTime($expires_at); 
					//echo "<div>expire_at: " . $date_expire->format('Y-m-d H:i:s') . "</div>";						
			
					if( ( $expires_at - time() ) > 0){ // Unix time
						$has_expired=false;
					}else{
						$has_expired=true;
					}					
				}
			}
					
			$go_user = null;
			$user = null;
 
				
// Il token va analizzato per verificare  permessi e scadenza

//	Verify the integrity of the ID token
//	
//	After you receive the ID token by HTTPS POST, you must verify the integrity of the token. To verify that the token is valid, ensure that the following criteria are satisfied:
//	- The ID token is properly signed by Google. Use Google's public keys (available in JWK or PEM format) to verify the token's signature.
//	- The value of aud in the ID token is equal to one of your app's client IDs. This check is necessary to prevent ID tokens issued to a malicious app being used to access data about the same user on your app's backend server.
//	- The value of iss in the ID token is equal to accounts.google.com or https://accounts.google.com.
//	- The expiry time (exp) of the ID token has not passed.
//	- If you want to restrict access to only members of your G Suite domain, verify that the ID token has an hd claim that matches your G Suite domain name.
//	Rather than writing your own code to perform these verification steps, we strongly recommend using a Google API client library for your platform, or calling our tokeninfo validation endpoint.

			$userData = $client->verifyIdToken($id_token); 	// Verifies the JWT signature, the aud claim, the iss claim, and the exp claim
															// Note: The library will automatically download and cache the certificate required for verification, and refresh it if it has expired.
															
			// Once you get these claims, you still need to check that the aud claim contains one
			// of your app's client IDs.
			// If it does, then the token is both valid and intended for your client,
			// and you can safely retrieve and use the user's unique Google ID from the sub claim.
	
			// 			echo "<div> TOKEN_DATA ";
			// 			echo @rt($userData); // html out format
			// 			echo "</div>";
			
			if(empty($userData)){
				throw new \UnexpectedValueException("\$userData is null or empty");
			}
			
			$go_user = self::getGoUserOrRegister($userData);
						
			if($go_user){
				$user = self::getUserOrRegister($userData);
				if(!$user){
					self::getLogger()->debug("\$user is null");
					throw new \RuntimeException("\$user is null");
				}
			}else{
				self::getLogger()->debug("\$go_user is null");
				throw new \RuntimeException("\$go_user is null");
			}
				
			if($user && $go_user){
				$login_successful = self::loginFromJs2($userData);
			}else{
				throw new \RuntimeException("Errore imprevisto");
			}
			
		}else{
			$msg = "Errore nel recuperare il bearer token";
			throw new \UnexpectedValueException($msg);
		}
		return $login_successful;
	}
		
	public static function stringToDateTime($str_timestamp){
		$date = null;
		$length = strlen($str_timestamp);
		if($length==13){
			$date = new \DateTime("@" . substr($str_timestamp, 0, 10));
		}else if($length==10){
			$date = new \DateTime("@" . $str_timestamp);
		}else{
			throw new \InvalidArgumentException("Conversion error, string length is " . $length);
		}
		return $date;
	}
	
	/**
	* Google Sign-In for server-side apps
	* @see https://developers.google.com/identity/sign-in/web/server-side-flow
	* This is a hybrid server-side flow where a user authorizes your app on the client side using the JavaScript API client 
	* and you send a special one-time authorization code to your server.
	* Your server exchanges this one-time-use code to acquire its own access and refresh tokens from Google for the server to be able to make its own API calls, which can be done while the user is offline.
	* This one-time code flow has security advantages over both a pure server-side flow and over sending access tokens to your server.
	*
	*/
	public static function loginServerSide(){
		$client = GoModel::getGoClient();
						
		if(isset($_REQUEST['code'])){
									
			$code = $_REQUEST['code'];
		
			// Exchange an authorization code for an access token.
			$accessToken = $client->fetchAccessTokenWithAuthCode($code);
			
			//	The code is your one-time code that your server can exchange for its own access token and refresh token. 
			//	You can only obtain a refresh token after the user has been presented an authorization dialog requesting offline access. 
			//	You must store the refresh token that you retrieve for later use because subsequent exchanges will return null for the refresh token. 
			//	This flow provides increased security over your standard OAuth 2.0 flow		
			//
			// 	print_r($accessToken);
			// 	will output:
			// 	array(4) {
			//  	 ["access_token"]=>
			//   	string(73) "ya29.FAKsaByOPoddfzvKRo_LBpWWCpVTiAm4BjsvBwxtN7IgSNoUfcErBk_VPl4iAiE1ntb_"
			//   	["token_type"]=>
			//   	string(6) "Bearer"
			//   	["expires_in"]=>
			//   	int(3593)
			//   	["created"]=>
			//   	int(1445548590)
			// 	}
			echo "access token: <div>" . @rt($accessToken) . "</div>" . PHP_EOL;
			$refreshToken = $client->getRefreshToken();
			echo "refresh token: <div>" . @rt($refreshToken) . "</div>" . PHP_EOL;
			self::storeAccessToken($accessToken, $refreshToken, $user_info->email);
			
		}else if (isset($_SESSION[Session::GOOGLE_ACCESS_TOKEN])) { // extract token from session and configure client
		
			self::getLogger()->debug("loginServerSide(): extracting token from session and configure client");
			
			$accessToken = Session::get(Session::GOOGLE_ACCESS_TOKEN);
			$refreshToken = Session::get(Session::GOOGLE_REFRESH_TOKEN);
			$client->setAccessToken($accessToken);
			$client->setRefreshToken($refreshToken);
			
			if ($client->isAccessTokenExpired()) {
				self::getLogger()->debug("loginServerSide(): calling fetchAccessTokenWithRefreshToken()...");	
				$accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);				
				// Se ho capito bene non devo invocare di nuovo getRefreshToken() perchè restituirebbe null. Verifico stampando i valori:
				$refreshToken2 = $client->getRefreshToken();
				echo "refresh token: <div>" . @rt($refreshToken) . "</div>" . PHP_EOL;
				echo "refresh token2: <div>" . @rt($refreshToken2) . "</div>" . PHP_EOL;
				self::storeAccessToken($accessToken, $refreshToken, $user_info->email);
				}
			  }
						
			
		if (!$client->getAccessToken() || $client->isAccessTokenExpired()) {
			unset($_SESSION[Session::GOOGLE_ACCESS_TOKEN]);
			unset($_SESSION[Session::GOOGLE_REFRESH_TOKEN]);
			self::storeAccessToken(null, null, $user_info->email);
			$authUrl = $client->createAuthUrl();
			self::getLogger()->debug("loginServerSide(): \$client->getAccessToken() returns null or an expired token");
			header('Location: ' . $authUrl);			
			die();
		}		
		
		self::loginServerSide2();		
	}	
 
	public static function writeNewGoUserToDatabase(array $userData){
		if(empty($userData)){
			throw new \InvalidArgumentException ("\$userData is null or empty");
		}

		$go_id = $userData['sub'];
		$email = $userData["email"];
		$display = null;
		$given_name = null;
		$family_name = null;		
		if(isset($userData["name"])){
			$display = $userData["name"];						
			$given_name = $userData["given_name"];
			$family_name = $userData["family_name"];
		}
		$pic_url = null;
		if(isset($userData["picture"])){
			$pic_url = $userData["picture"];
		}
		$now = new \DateTime();
		$now->setTimestamp(time());
		$ip = self::getRequestIp();
 
		$go_user = new Userexternal();
		$go_user->setId($go_id);
		$go_user->setDisplay($display);
		$go_user->setEmail($email);
		$go_user->setFirstName($given_name);
		$go_user->setMiddleName(null);
		$go_user->setLastName($family_name);
		$go_user->setPictureUrl($pic_url);
		$go_user->setCreationtime($now);
		$go_user->setCreationip($ip);
		$go_user->setProvidertype(UserModel::PROVIDER_TYPE_GO);
		try {
			$em = DbResource::getEntityManager();
			$em->persist($go_user);
			$em->flush();
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
	
	public static function logout(){
		GoModel::$go_client->revokeToken();
		// e forse anche...
		// GoModel::$go_client = null;
		// Inoltre se sono in presenza di login server-side devo anche distruggere la sessione con:
		//
		// unset($_SESSION[Session::GOOGLE_ACCESS_TOKEN]);	
		//session_destroy();
			
	}
}
