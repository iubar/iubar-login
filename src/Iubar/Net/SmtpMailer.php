<?php

namespace Iubar\Net;

// RIFERIMENTI
// Codici erorri: http://www.greenend.org.uk/rjk/tech/smtpreplies.html SMTP reply codes
// Peronalizzazione degli headers smtp: http://help.mandrill.com/entries/21688056-Using-SMTP-Headers-to-customize-your-messages
// La seguente classe utilizza la classe Swift Mailer: http://swiftmailer.org/

// port 25 or 587 for unencrypted / TLS connections
// port 465 for SSL connections


use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Plugins_Loggers_ArrayLogger;
use Swift_Plugins_LoggerPlugin;
use Swift_Message;
use Swift_Attachment;
use Swift_TransportException;
use Psr\Log\LogLevel;

class SmtpMailer {

	public $smtp_usr = null;
	public $smtp_pwd = null;
	public $subject = null;
	public $to_array = array(); // ie: array('some@address.tld' => 'The Name')
	public $body_txt = null;
	public $body_html = null;
	public $attachments = array(); // ie: array('/path/to/image.jpg'=>'image/jpeg');
	public $from_array = array();
	
	private $smtp_host = null;
	private $agent_logger_enabled = null;
	private $logger = null;
	
	public function __construct($logger=false, $agent_logger_enabled=false){
		$this->agent_logger_enabled = $agent_logger_enabled;
		$this->logger = $logger;
	}
	
	public function setFrom($email, $name=""){
		if(is_array($email)){
			// in questa situazione il valore di $name viene ignorato
			$this->from_array = $email;
		}else{		
			if($name){			
				$this->from_array[$email] = $name;
			}else{
				$this->from_array[] = $email;
			}
		}
	}

	public function setSubject($subject){
		$this->subject = $subject;
	}
	
	public function setToList($array){
		$this->to_array = $array;
	}
	
	public function setTo($to){
		$this->setToList($to);
	}
	
	public function setBodyHtml($html){
		$this->body_html = $html;
	}
	
	public function setBodyTxt($txt){
		$this->body_txt = $txt;
	}
	
	public function setSmtpUser($user){
		$this->smtp_usr = $user;
	}
	
	public function setSmtpPassword($password){
		$this->smtp_pwd = $password;
	}
	
	public function addAttachment($filename, $type=null){
		$this->attachments[$filename] = $type;
	}
	
	public function sendByMailJet(){
		return $this->send($this->getMailJetTransport());
	}	
	public function sendByAruba($ssl=false){				
		return $this->send($this->getArubaTransport($ssl));
	}
	public function sendByGmail(){		
		return $this->send($this->getGmailTransport());
	}	
	public function sendByMandrill($port=null){
		return $this->send($this->getMandrillTransport($port));
	}	
	public function sendBySendGrid($port=null){		
		return $this->send($this->getSendGridTransport($port));
	}	
	public function sendByMailgun($port=null){
		return $this->send($this->getMailgunTransport($port));
	}

	
	public function getMailJetTransport(){
		// Create the Transport
		
		// 	Port 25 or 587 (some providers block port 25)
		// 	If TLS on port 587 doesn't work, try using port 465 and/or using SSL instead
		
		
 		$transport = Swift_SmtpTransport::newInstance("in-v3.mailjet.com", 587, 'tls')
 		->setUsername($this->smtp_usr) // API KEY
 		->setPassword($this->smtp_pwd); // SECRET KEY
 		return $transport;
	}
	
	public function getArubaTransport($ssl=false){
		// Create the Transport
		$transport = null;
		if($ssl){
			$transport = Swift_SmtpTransport::newInstance("smtps.aruba.it", 465, 'ssl');
		}else{
			$transport = Swift_SmtpTransport::newInstance("smtp.iubar.it", 25);
		}		
		$transport->setUsername($this->smtp_usr)->setPassword($this->smtp_pwd);
		return $transport;
	}
	
	public function getMandrillTransport($port=null){
		
		if ($port === null){
			$port = 587;
		}
		
		// Which SMTP ports can I use with Mandrill ?
		// You can use port 25, 587, or 2525 if you're not encrypting the communication between 
		// your system and Mandrill or if you want to use the STARTTLS extension (also known as TLS encryption). 
		// SSL is supported on port 465.		
		// ISPs may redirect traffic on certain ports, so it's up to you which port you use.

		// Create the Transport
		$transport = Swift_SmtpTransport::newInstance("smtp.mandrillapp.com", $port, 'tls')
		->setUsername($this->smtp_usr)
		->setPassword($this->smtp_pwd)
		->setTimeout(8); // 8 secondi
		return $transport;
	}
	
	public function getSendGridTransport($port=null){
		// Create the Transport
		
		if ($port === null){
			$port = 587;
		}
			
		// 	Integrate with Sendgrid using SMTP
			
		// 	Change your SMTP authentication username and password to your SendGrid username and password, or set up a Multiple Credential with “mail” enabled.
		// 	Set the server host to smtp.sendgrid.net. This setting can sometimes be referred to as the external SMTP server, or relay, by some programs and services.
		// 	Ports
						
		// You can connect via unencrypted or TLS on ports 25, 2525, and 587. 
		// You can also connect via SSL on port 465. Keep in mind that many hosting providers and ISPs block port 25 as a default practice. If this is the case, contact your host/ISP to find out which ports are open for outgoing smtp relay.			
		// We recommend port 587 to avoid any rate limiting that your server host may apply.
						
		$transport = Swift_SmtpTransport::newInstance("smtp.sendgrid.net", $port, 'tls')
		->setUsername($this->smtp_usr)
		->setPassword($this->smtp_pwd);	
		return $transport;
	}	
	public function getGmailTransport(){
		
// Attenzione: dal 2016 non si può più utilizzare l'smtp di Google con le opzioni di default.
// Vedere :
// https://www.google.com/settings/security/lesssecureapps
// https://support.google.com/accounts/answer/6010255?hl=it

// 		I limiti di utilizzo dei servizi SMTP di google sembrerebbe il seguente (i dati non sono ufficiali)
//		
// 		== Gmail ==
// 			500 per day 20 emails / hour
//		
// 		== Google Apps ==
// 			Messages per day 2000			
// 			Messages auto-forwarded 10,000			
// 			Auto-forward mail filters 20			
// 			Recipients per message 2000(500 external)			
// 			Total recipients per day 10,000			
// 			External recipients per day 3000			
// 			Unique recipients per day 3000(2000 external)			
// 			Recipients per message (sent via SMTP by POP or IMAP users) 99
		
		// Create the Transport
		$transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
		->setUsername($this->smtp_usr)
		->setPassword($this->smtp_pwd);
		return $transport;
	}
	
	public function getMailgunTransport($port=null){
		// Create the Transport
		
		if ($port === null){
			$port = 465;
		}
		
		// Mailgun servers listen on ports 25, 465 (SSL/TLS), 587 (STARTTLS), and 2525	
		
		$transport = Swift_SmtpTransport::newInstance ('smtp.mailgun.org', $port)
		->setUsername($this->smtp_usr)
		->setPassword($this->smtp_pwd)
		->setTimeout(8); // 8 secondi
		
		//Helps for sending mail locally during development
		$transport->setLocalDomain ( '[127.0.0.1]' );

		return $transport;
	}
		
	private function createMessage(){
		// Create a message
		// Deafult Character Set is UTF8 (http://swiftmailer.org/docs/messages.html)
		$message = Swift_Message::newInstance($this->subject)
		->setFrom($this->from_array) 				// From: addresses specify who actually wrote the email
		->setTo($this->to_array);
		// ->setBcc(array('some@address.tld' => 'The Name'))
		// ->setSender('your@address.tld') 			// Sender: address specifies who sent the message
		// ->setReturnPath('bounces@address.tld') 	// The Return-Path: address specifies where bounce notifications should be sent
		
		if(!$this->body_html){
			$message->setBody($this->body_txt);
		}else{
			$message->setBody($this->body_html, 'text/html');
			if(!$this->body_txt){
				$message->addPart($this->body_txt, 'text/plain');
			}
		}
		
		// Or set it like this
		// $message->setBody('My <em>amazing</em> body', 'text/html');
		// Add alternative parts with addPart()
		// $message->addPart('My amazing body in plain text', 'text/plain');
		
		// The priority of a message is an indication to the recipient what significance it has. Swift Mailer allows you to set the priority by calling the setPriority method. This method takes an integer value between 1 and 5:
		// 	Highest
		// 	High
		// 	Normal
		// 	Low
		// 	Lowest
		// $message->setPriority(2);
		
		
		// ATTACHMENTS
		foreach($this->attachments as $filename=>$type){
			// * Note that you can technically leave the content-type parameter out
			$attachment = Swift_Attachment::fromPath($filename, $type); // ...->setFilename('cool.jpg');
			// Create the attachment with your data
			// $data = create_my_pdf_data();
			// $attachment = Swift_Attachment::newInstance($data, 'my-file.pdf', 'application/pdf');
			// Attach it to the message
			if(is_file($filename)){
				$message->attach($attachment);
			}else{
				$this->log(LogLevel::ERROR, "Attachment not found: " . $filename);
			}
		}
		return $message;
	}
	
	private function send($transport){
	
		$result = 0;
		
		$smtp_usr = $transport->getUsername();
		$smtp_pwd = $transport->getPassword();
		
		if(!$smtp_usr || !$smtp_pwd){
				die("QUIT: smtp user or password not set" . PHP_EOL);
		}else{
			// Create the Mailer using your created Transport
			$mailer = Swift_Mailer::newInstance($transport);
		
			if($this->agent_logger_enabled){
				
				// To use the ArrayLogger
				$mail_logger = new Swift_Plugins_Loggers_ArrayLogger();
				$mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($mail_logger));
		
				// Or to use the Echo Logger
				// $mail_logger = new Swift_Plugins_Loggers_EchoLogger();
				// $mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($mail_logger));
			}
	
			// Send the message
			$failures = array();
			try{
				$result = $mailer->send($this->createMessage(), $failures);
				if(count($failures)>0){
					$this->log(LogLevel::WARNING, "Result: " . $result);
					$this->log(LogLevel::ERROR, "There was an error");
					foreach ($failures as $key=>$value){
						$this->log(LogLevel::ERROR, $key . " ==> " . $value);
					}
				}else{
					$this->log(LogLevel::INFO, "Message successfully sent!");
					$this->log(LogLevel::INFO, "Result: " . $result);
				}
					
				if($this->agent_logger_enabled){
					// Dump the log contents
					// NOTE: The EchoLogger dumps in realtime so dump() does nothing for it
					$this->log(LogLevel::INFO, "Logger output is:");
					$this->log(LogLevel::INFO, $mail_logger->dump());
					$this->log(LogLevel::INFO, "Done.");
				}
			}catch(Swift_TransportException $e){
				// Il messaggio non è stato inviato
	        	$this->log(LogLevel::ERROR, $e->getMessage());
	        }catch(\Exception $e){
	        	$this->log(LogLevel::ERROR, $e->getMessage());
	        }
	   		
		}
   		
   		return $result;
	}

	private function log($level, $msg){
		if($this->logger){
			$this->logger->log($level, $msg);
		}
	}
		
	public static function getDomainFromEmail($email){
		// Get the data after the @ sign
		$array = explode('@', $email);
		$domain = $array[1];
		// oppure 
		// $domain = substr(strrchr($email, "@"), 1);
		return $domain;
	}
	

	
} // end class