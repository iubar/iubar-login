<?php

namespace Iubar\Login\Interfaces;

interface IRegistration {
	
	public function writeNewUserToDatabase($user_name, $user_password_hash, $user_email, $user_activation_hash, $provider_type);
				
}
