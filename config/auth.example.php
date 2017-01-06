<?php

/**
 * Configuration for: Error reporting
 * Useful to show every little problem during development, but only show hard / no errors in production.
 * It's a little bit dirty to put this here, but who cares. For development purposes it's totally okay.
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);
/**
 * Configuration for cookie security
 * Quote from PHP manual: Marks the cookie as accessible only through the HTTP protocol. This means that the cookie
 * won't be accessible by scripting languages, such as JavaScript. This setting can effectively help to reduce identity
 * theft through XSS attacks (although it is not supported by all browsers).
 *
 * @see php.net/manual/en/session.configuration.php#ini.session.cookie-httponly
 */
ini_set('session.cookie_httponly', 1);

return array (
    
    // Avatar
    'auth.avatar.path' => realpath(dirname(__FILE__).'/../../') . '/public/avatars/',
    'auth.avatar.path.public' => 'avatars/',
    
    // Cookie
    'auth.cookie.runtime' => 1209600,
    'auth.cookie.path' => '/',
    'auth.cookie.domain' => '',
    'auth.cookie.secure' => false,
    'auth.cookie.http' => true,
    'auth.session.runtime' => 604800,
    
    // Gravatar
    'auth.gravatar.enabled' => true,
    'auth.gravatar.imageset' => 'mm',
    'auth.gravatar.rating' => 'pg',
    'auth.avatar.size' => 44,
    'auth.avatar.quality' => 85,
    'auth.avatar.default' => 'default.jpg',
    
    // Encryption
    'auth.encryption.key' => 'encryption key here',
    'auth.hmac.salt' => '9qk0c^5L7d#26tM9z8n1%',
    
    // Routes
    'auth.routes.password-reset' => 'login/password-reset',
    'auth.routes.verification' => 'register/verify',
    'auth.routes.pwdreset' => 'login/password-reset',
    'auth.routes.afterlogin' => '/dashboard',
    
    // Emails
    'email.passwordreset.subject' => 'Password reset',
    'email.passwordreset.content' => 'Clicca nel seguente link per resettare la tua password: ',
    'email.verification.subject' => 'Attivazione account',
    'email.verification.content' => 'Clicca nel seguente link per attivare il tuo account: ',
    'email.welcome.subject' => 'Benvenuto',
    'email.welcome.content' => 'Benvenuto...',
    'email.pwdreset.subject' => 'Recupero password',
    'email.pwdreset.content' => 'Clicca nel seguente link per resettare la tua password: ',
    
    // Views
    // TODO: rinominare i file
    'auth.views.fb-login-server-side' => 'login/external/fb-login-server-side.twig',
    'auth.views.fb-button' => 'login/external/fb-button.twig',
    'auth.views.fb-logout' => 'login/external/fb-logout.twig',
    'auth.views.go-login-server-side' => 'login/external/go-login-server-side.twig',
    'auth.views.go-custom-button' => 'login/external/go-custom-button.twig',
    'auth.views.go-button' => 'login/external/go-button.twig',
    'auth.views.go-logout' => 'login/external/go-logout.twig',
    'auth.views.request-password-reset' => 'login/request-password-reset.twig',
    'auth.views.password-dimenticata' => 'login/password-dimenticata.twig',
    'auth.views.password-reset' => 'login/password-reset.twig', // TODO: includere
    'auth.views.index' => 'login/index.twig',
    'auth.views.verify' => 'login/verify.twig',
    
    // Google recaptcha
    'auth.captcha.enabled' => true,
    'auth.captcha.key' => 'key',
    'auth.captcha.secret' => 'secret',
    
    'auth.email.verification.enabled' => false,
    
);