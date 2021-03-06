<?php

namespace Iubar\Login\Core;

use Iubar\Net\SmtpMailer;
use Iubar\Login\Models\AbstractLogin;

class EmailSender {

    private $app = null;
    private $m = null;

	public function __construct(){
            $this->app = \Slim\Slim::getInstance();
	    $this->m = SmtpMailer::factory('amazonses');
	}

	protected function config($key){
		return AbstractLogin::config($key);
	}

	public function go($transactional = false){
	    if (count($this->m->from_array) <= 0 || $this->m->from_array == null){
	        if ($transactional){
	            $this->setFrom($this->config('email.transactional'), $this->config('app.name'));
	        } else {
	            $this->setFrom($this->config('email.postmaster'), $this->config('app.name'));
	        }
	    }
	    $this->m->smtp_usr = $this->app->config('email.aws.user');
	    $this->m->smtp_pwd = $this->app->config('email.aws.password');
	    $this->m->smtp_port = $this->app->config('email.smtp.port');
		return $this->m->send();
	}

	public function setFrom($from_email, $from_name){
	    $this->m->setFrom($from_email, $from_name);
	}

	public function setTo($to){
	    $this->m->setTo($to);
	}

	public function setSubject($subject){
	    $this->m->setSubject($subject);
	}

	public function setBodyHtml($message){
	    $this->m->setBodyHtml($message);
	}

}
