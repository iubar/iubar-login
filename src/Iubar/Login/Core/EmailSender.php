<?php

namespace Iubar\Login\Core;

use Iubar\Net\SmtpMailer;

class EmailSender {
   
    private $app = null;
    private $m = null; 
    
	public function __construct(){
	    $this->app = \Slim\Slim::getInstance();
// 	    $monolog_writer = $this->app ->config('log.writer');
// 	    if ($monolog_writer !== null){
// 	       $logger = $monolog_writer->get_resource();
// 	    }else{
// 	        die("\$monolog_writer IS NULL");
// 	    }
// 	    if($logger==null){
// 	        die("LOGGER IS NULL");
// 	    }
	    $this->m = SmtpMailer::factory('mailgun');
// 	    $this->m->setLogger($logger);
// 	    $this->m->enableAgentLogger(true);
  
	}
	
	public function go($transactional = false){
	    if (count($this->m->from_array) <= 0 || $this->m->from_array == null){
	        if ($transactional){
	            $this->setFrom($this->app->config('email.transactional'), $this->app->config('app.name'));
	        } else {
	            $this->setFrom($this->app->config('email.postmaster'), $this->app->config('app.name'));
	        }
	    }
	    
 	    $this->m->smtp_usr = $this->app->config('email.user');
	    $this->m->smtp_pwd = $this->app->config('email.mailgun.password');
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
