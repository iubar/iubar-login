<?php

namespace Iubar\Login\Core;

class EmailSender extends \Iubar\Net\SmtpMailer { // TODO: aggiornare
	
	public function __construct(){
	
	}
	
	public function go($transactional = false){
		$b = false;

		$app = \Slim\Slim::getInstance();
		
		if (count($this->from_array) <= 0 || $this->from_array == null){
			if ($transactional){
				$this->setFrom($app->config('email.transactional'), $app->config('app.name'));
			} else {
				$this->setFrom($app->config('email.postmaster'), $app->config('app.name'));
			}
			$this->setSmtpUser($app->config('email.smtp'));
			$this->setSmtpPassword($app->config('email.mailgun.password'));
		}
		
		$result = $this->sendByMailgun($app->config('email.smtp.port'));
		if ($result > 0){
			$b = true;
		}
		
		return $b;
	}	
}
