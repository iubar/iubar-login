<?php

return array (

	// App
	'app.name' => 'app name here',
	'app.templates.path' => 'app',
	
	// Global
	'avatar.path' => realpath(dirname(__FILE__).'/../../') . '/public/avatars/',
	'avatar.path.public' => 'avatars/',
	'cookie.runtime' => 1209600,
	'cookie.path' => '/',
	'cookie.domain' => '',
	'cookie.secure' => false,
	'cookie.http' => true,
	'session.runtime' => 604800,
	'gravatar.enabled' => true,
	'gravatar.imageset' => 'mm',
	'gravatar.rating' => 'pg',
	'avatar.size' => 44,
	'avatar.quality' => 85,
	'avatar.default' => 'default.jpg',
	'encryption.key' => 'encryption key here',
	'hmac.salt' => '9qk0c^5L7d#26tM9z8n1%',
	'email.passwordreset.url' => 'login/password-reset',
	'email.passwordreset.subject' => 'Password reset',
	'email.passwordreset.content' => 'Clicca nel seguente link per resettare la tua password: ',
	'email.verification.url' => 'register/verify',
	'email.verification.subject' => 'Attivazione account',
	'email.verification.content' => 'Clicca nel seguente link per attivare il tuo account: ',
	'email.welcome.subject' => 'Benvenuto',
	'email.welcome.content' => 'Benvenuto...',
	'email.pwdreset.url' => 'login/password-reset',
	'email.pwdreset.subject' => 'Recupero password',
	'email.pwdreset.content' => 'Clicca nel seguente link per resettare la tua password: ',

	// Email
	'email.mailgun.password' => 'password',
	'email.smtp' => 'smpt',
	'email.postmaster' => 'postmaster',
	'email.transactional' => 'transactional',
		
	// Google
	'captcha.enabled' => true,
	'captcha.key' => 'key',
	'captcha.secret' => 'secret',
		
	// Auth
	'auth.email.verification.enabled' => false,
	'auth.route.afterlogin' => '/dashboard',
);