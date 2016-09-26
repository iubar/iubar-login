  window.fbAsyncInit = function() {
	  FB.init({
	    appId      : facebook_app_id,
	    cookie     : true,  // enable cookies to allow the server to access the session
	    xfbml      : true,  // parse social plugins on this page
	 // status	   : true,	// Determines whether the current login status of the user is freshly retrieved on every page load. If this is disabled, that status will have to be manually retrieved using .getLoginStatus().
							// To receive the response of this call, you must subscribe to the auth.statusChange event.
							// FB.Event.subscribe('auth.statusChange', auth_status_change_callback);
	    
	    version    : 'v2.4'
	  });
	
	  // Now that we've initialized the JavaScript SDK, we call 
	  // FB.getLoginStatus().  This function gets the state of the
	  // person visiting this page and can return one of three states to
	  // the callback you provide.  They can be:
	  //
	  // 1. Logged into your app ('connected')
	  // 2. Logged into Facebook, but not your app ('not_authorized')
	  // 3. Not logged into Facebook and can't tell if they are logged into
	  //    your app or not.
	  //
	  // These three cases are handled in the callback function.
	
	  FB.getLoginStatus(function(response) {
// 		  1) the user is logged into Facebook and has authenticated your application (connected)
// 		  2) the user is logged into Facebook but has not authenticated your application (not_authorized)
// 		  3) the user is not logged into Facebook at this time and so we don't know if they've authenticated your application or not (unknown)		  		  
	    statusChangeCallback(response);
	  });

	  FB.Event.subscribe("auth.logout", function() {
	       window.location = '/'
	  });
	  
  };
  
  function fb_login(){
		FB.getLoginStatus(function(response) {
			var status = response['status'];
			console.log('fb_login() status is: ' + status); 	
				
			if (status === 'connected') {
			   	// Logged into your app and Facebook.
			   	console.log('Logged in.');

				var url = "login/fb/jscallback";
				 var redirect = getParameterByName('redirect');

				 if (redirect != null){
					url = url + "?redirect=" + redirect;
				}
			   				 
				 window.location.replace(url);
		   	} else {
			   	// The person is logged into Facebook, but not your fb-app.
			   	// OR
			   	// The person is not logged into Facebook, so we're not sure if
		      	// they are logged into this app or not.
				fb_login2();
			}
	   	});
	}

	function fb_login2(){
	    FB.login(function(response) { // Handle the response object, like in statusChangeCallback()

	        if (response.status === 'connected') {
	         // oppure  if (response.authResponse) {
	        		    // Logged into your app and Facebook.
	                    console.log('Welcome! Your are connected. Now I am fetching some information about you.... ');
	                    access_token = response.authResponse.accessToken; // get access token
	                    console.log('accessToken: ' + access_token);
	                    user_id = response.authResponse.userID; // get FB UID
	              
	        			var url = "login/fb/jscallback";
	       			 	var redirect = getParameterByName('redirect');

	       			 	if (redirect != null){
	       					url = url + "?redirect=" + redirect;
	       				}
	       		   				 
	       			 	window.location.replace(url);

	        		 	// Get friends    		 	
	        		 	// In v2.0 of the Graph API, calling /me/friends returns the person's friends who also use the app    		 	 
	                    // FB.api('/me/friends', function(response) { // invitable_friends only for games
	                    //    console.log('friends: ' + JSON.stringify(response));
	                    //    var friend_data = response.data.sort(sortMethod);
	    				//
	                    //    var results = '';
	                    //    for (var i = 0; i < friend_data.length; i++) {
	                    //        results += '<div><img src="https://graph.facebook.com/' + friend_data[i].id + '/picture">' + friend_data[i].name + '</div>';
	                    //    }
	    				//
	                    //    // and display them at our holder element
	                    //    console.log('Result list of your friends: ' + results);
	                    // });

	                    // Il seguente codice è errato perchè il percorso '/me/user_likes' non esiste
	                    // FB.api('/me/user_likes', function(response) { // invitable_friends only for games
	                    //   console.log('user_likes: ' + JSON.stringify(response));                   
	                    //});      
	                        		 	
	        	 } else if (response.status === 'not_authorized') {
	        		    // The person is logged into Facebook, but not your app.
	        		 	console.log('User not fully authorize.');
	        	 } else {
	        		    // The person is not logged into Facebook, so we're not sure if
	        		    // they are logged into this app or not.
	        		 	console.log('User not logged in.');
	        	 }
	    		          
	        }, {
//	         	Here are a few examples of actions that will require user approval:
//	         		You won't be able to access a user's email address unless you ask for the email permission.
//	         		You won't be able to post something on a user's timeline unless your ask for the publish_actions permission.
//	         		You won't be able to upload a photo album for a user unless your ask for both the user_photos permission and publish_actions permission.    		       
	            scope: 'public_profile, email', // ,user_likes, user_friends'
	            auth_type: 'https'
	        }); // end Fb.login()
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
	 
	 function fbLogout(response) {
		 if (response.status === 'connected') {
			    // Logged into your app and Facebook.
				 	console.log('you\'re connected to fb');
					FB.logout(function(response) {
						// Person is now logged out
						console.log('logged out');
						document.getElementById('status').innerHTML = 'Disconnesso da FB';
					});
								 
			  } else if (response.status === 'not_authorized') {
			    // The person is logged into Facebook, but not your app.
				  console.log('you\'re not authorized');
			  } else {
			    // The person is not logged into Facebook, so we're not sure if
			    // they are logged into this app or not.
				  document.getElementById('status').innerHTML = 'Can\'t logout: you aren\'t logged in.';
			  }

			 // window.location = "{{ redirect_to }}"; 	

	}

  // Load the SDK asynchronously
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/it_IT/sdk.js";
    // oppure js.src = "//connect.facebook.net/it_IT/sdk.js#xfbml=1&version=v2.5&appId=711000465694649";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));



  // https://developers.facebook.com/docs/javascript

  function sortMethod(a, b) {
    var x = a.name.toLowerCase();
    var y = b.name.toLowerCase();
    return ((x < y) ? -1 : ((x > y) ? 1 : 0));
}

function fbLogout() {
    FB.logout(function (response) {
        //window.location.replace("http://stackoverflow.com"); is a redirect
        // window.location.reload();
        console.log('I\'m FB.logout()');
    });
}

  // This is called with the results from from FB.getLoginStatus().
  function statusChangeCallback(response) {

	  
// Example of a "response" value:
//	
// 	    {
// 		    status: 'connected',
// 		    authResponse: {
// 		        accessToken: '...',
// 		        expiresIn:'...',
// 		        signedRequest:'...',
// 		        userID:'...'
// 		    }
// 		}
			  
    console.log('statusChangeCallback: ' + angular.toJson(response));

    var status = response['status']; // oppure  "response.status";
    console.log('fb status: ' + status);    
    var authResponse = response['authResponse'];
    if(authResponse){
	    var accessToken = authResponse['accessToken'];
	    var expiresIn = authResponse['expiresIn'];
	    var signedRequest = authResponse['signedRequest'];
	    var userID = authResponse['userID'];
	    console.log('accessToken: ' + accessToken);
	    console.log('expiresIn: ' + expiresIn);
	    console.log('signedRequest: ' + signedRequest);
	    console.log('userID: ' + userID);	    
  	}


    
    // The response object is returned with a status field that lets the
    // app know the current login status of the person.
    // Full docs on the response object can be found in the documentation
    // for FB.getLoginStatus().
    

          console.log('fb response.status: ' + response.status); 
    if (response.status === 'connected') {
    	console.log('if ***********************');
      // Logged into your app and Facebook.
     // document.getElementById('social_status').innerHTML = 'You are logging in FB as ' + userID + '!';
	  updateFbImage();      
    } else if (response.status === 'not_authorized') {
      // The person is logged into Facebook, but not your fb-app.
    //  document.getElementById('social_status').innerHTML = 'Please log into this app.';
    } else {
      // The person is not logged into Facebook, so we're not sure if
      // they are logged into this app or not.
     // document.getElementById('social_status').innerHTML = "Please log into Facebook";
    }
  }

  // This function is called when someone finishes with the Login
  // Button.  See the onlogin handler attached to it in the sample
  // code below.
  function checkLoginState() {
    FB.getLoginStatus(function(response) { // Checking login status
      statusChangeCallback(response);
    });
  }

  function updateFbImage(){ // This should be called only if you know the user is authenticated. 
		console.log('updateFbImage !!!');
		console.log('***********************');
	 	FB.api('/me', function(response) {
	 		
	 	    console.log(JSON.stringify(response));
			// Example of a "response" value:
			// 	   {
			//  	  "id":"101540562372987329832845483",
			//  	  "email":"example@example.com",
			//  	  "first_name":"Bob",
			//  	  [ ... ]
			//  	}

	 	   console.log('Successful login for: ' + response.name);
	  		
	  		user_email = response.email;
	  		user_first_name = response.first_name;
	  		user_last_name = response.last_name;
	  		user_link = response.link;
	  		user_id = response.id;
	  		user_verified = response.verified;	            
			if(response.id){
	  			document.getElementById('social_img').innerHTML = '<img src="https://graph.facebook.com/' + response.id + '/picture">';
	 		}
	      //  document.getElementById('social_status').innerHTML = 'You are logged in FB as ' + response.name + '!';
	  		
	  		
	 	});
	}