  var auth2; // The Sign-In object.
  var googleUser; // The current user

  /**
   * Calls startAuth after Sign in V2 finishes setting up.
   */  
   var global_option = 0;
  var startAuth = function(option) {
	  console.log("startAuth()");
	  global_option = option;
    gapi.load('auth2', initSigninV2);       
  };

  
  /**
   * Initializes Signin v2 and sets up listeners.
   */
  var initSigninV2 = function(){

	      // Retrieve the singleton for the GoogleAuth library and set up the client.
      auth2 = gapi.auth2.init({
	        client_id: google_client_id,
	        cookiepolicy: 'single_host_origin', // default
	        // fetch_basic_profile: true,  // default
	        // Request scopes in addition to 'profile' and 'email'        
	       //scope: 'additional_scope'
	     //  scope: 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
	       scope: 'profile email'
		//	scope: 'profile'
	      });   // end of gapi.auth2.init(

	   auth2 = gapi.auth2.getAuthInstance();

 console.log("option = " + global_option);
 if(global_option==1){
	// Attach the click handler to the sign-in button
 	auth2.attachClickHandler('customGoogleBtn', {}, onSuccess, onFailure);
 }

// Listen for sign-in state changes.
auth2.isSignedIn.listen(signinChanged);

// Listen for changes to current user.
auth2.currentUser.listen(userChanged);

// Sign in the user if they are currently signed in.

var isSignedIn = auth2.isSignedIn.get();
console.log('isSignedIn: '+ isSignedIn);	    
if (isSignedIn != true) {
// Per forzare il login in automatico potrei scrivere:
//  auth2.signIn().then(function() {
//    console.log(auth2.currentUser.get().getId());
//   });     
}

// Start with the current live values.
//refreshValues();

    }; // end of initSigninV2()
  

 

  
  /**
   * Handle successful sign-ins.
   */
  var onSuccess = function(user) {
      console.log('Signed in as ' + user.getBasicProfile().getName());
	//	document.getElementById('social_status').innerText = "Signed in: " + googleUser.getBasicProfile().getName();
		if (auth2.isSignedIn.get()) {
			signIn(user);
		}else{
			console.log("******* SITUAZIONE IMPREVISTA ********");
			}
		logProfile();
   };

  /**
   * Handle sign-in failures.
   */
  var onFailure = function(error) {
	  console.log("onFailure()");
      console.log(error);
    alert(JSON.stringify(error));
  };
 
   var revokeAllScopes = function() {
	var auth2 = gapi.auth2.getAuthInstance();
    auth2.disconnect();
  }
  
 function signOut() {
    var auth2 = gapi.auth2.getAuthInstance();
    auth2.signOut().then(function () {
      console.log('User signed out.');
    });
	// revokeAllScopes();
  }
   
  function signIn(googleUser) {
	  console.log("signIn()");
		logProfile();
		 var response = googleUser.getAuthResponse();
 		 var id_token = response.id_token;
		 var access_token = response.access_token; // Questo token da ora in poi non ha alcun utilizzo pratico
		 console.log('response: ' + JSON.stringify(response));
		 console.log('id_token: ' + JSON.stringify(id_token));
		 var url = "login/google/callback?bearer_token=" + JSON.stringify(response); 
		 var redirect = getParameterByName('redirect');

		 if (redirect != null){
			url = url + "&redirect=" + redirect;
		}
	   				 
		window.location.replace(url); // see https://developers.google.com/identity/sign-in/web/backend-auth
		 
	}

 ///////////////////////////// LISTENERS
 
  

  /**
   * Listener method for sign-out live value.
   *
   * @param {boolean} val the updated signed out state.
   */
  var signinChanged = function (val) {
    console.log('Signin state changed to ', val);
    //document.getElementById('signed-in-cell').innerText = val;
    logProfile();    
  };

  /**
   * Listener method for when the user changes.
   *
   * @param {GoogleUser} user the updated user.
   */
  var userChanged = function (user) {
    console.log('userChanged: ', user);
    googleUser = user;
    //updateGoogleUser();
    logProfile();
    updateGoogleImage();
    //document.getElementById('curr-user-cell').innerText = JSON.stringify(user, undefined, 2);
  };

  ///////////////////////////////////////
  
  /**
   * Updates the properties in the Google User table using the current user.
   */
//   var updateGoogleUser = function () { // NON UTILIZZATO
//     if (googleUser) {
//       document.getElementById('user-id').innerText = googleUser.getId();
//       document.getElementById('user-scopes').innerText = googleUser.getGrantedScopes();
//       document.getElementById('auth-response').innerText = JSON.stringify(googleUser.getAuthResponse(), undefined, 2);
//     } else {
//       document.getElementById('user-id').innerText = '--';
//       document.getElementById('user-scopes').innerText = '--';
//       document.getElementById('auth-response').innerText = '--';
//     }
//       if (auth2.isSignedIn.get()) {
//       	document.getElementById('user-status').innerText = 'LOGGATO';
//      }
//       logProfile();
//   };

  /**
   * Retrieves the current user and signed in states from the GoogleAuth
   * object.
   */
//   var refreshValues = function() { // NON UTILIZZATO
//     if (auth2){
//       console.log('Refreshing values...');

//       googleUser = auth2.currentUser.get();

//       document.getElementById('curr-user-cell').innerText = "PLUTO" + JSON.stringify(googleUser);
//       document.getElementById('signed-in-cell').innerText = "PIPPO" + auth2.isSignedIn.get();

//       updateGoogleUser();
//     }
//   }
  
////////////////////// ALTRE FUNZIONI

  function logProfile(){
	  if(googleUser){
		  	JSON.stringify(googleUser);
			var profile = googleUser.getBasicProfile();
			if(profile){
				  console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
				  console.log('Name: ' + profile.getName());
				  console.log('Image URL: ' + profile.getImageUrl());
				  console.log('Email: ' + profile.getEmail());

			}else{
				  console.log('Profilo nullo: ' + JSON.stringify(googleUser));
			}
				var userScopes = googleUser.getGrantedScopes();
				console.log('userScopes: ' + authResponse);
				var authResponse = JSON.stringify(googleUser.getAuthResponse(), undefined, 2);
				console.log('authResponse: ' + authResponse);
				
		}else{
			console.log('googleUser is null');
		}
	}

  function updateGoogleImage(){
		var profile = googleUser.getBasicProfile();
		if(profile){
			var name = profile.getName();
	 		var pic = profile.getImageUrl();
	 		if(pic){
				document.getElementById('social_img').innerHTML = '<img src="' + pic + '">';
	 		}
       	// document.getElementById('social_status').innerHTML = 'You are logged in Google as ' + name + '!';
		}
	}

  function getParameterByName(name, url) {
	    if (!url) url = window.location.href;
	    name = name.replace(/[\[\]]/g, "\\$&");
	    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
	        results = regex.exec(url);
	    if (!results) return null;
	    if (!results[2]) return '';
	    return decodeURIComponent(results[2].replace(/\+/g, " "));
	}
  
  startAuth(1);