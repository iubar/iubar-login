<?php

namespace Iubar\Login\Models;

abstract class AbstractLogin {

	static $app = null;
	
	private static function getAppInstance(){
		if(self::$app == null){
			self::$app =  \Slim\Slim::getInstance();
		}
		return self::$app;
	}
	
	protected static function getLogger(){
		return self::getAppInstance()->log;
	}
	
	protected static function config($key){
		return self::getAppInstance()->config($key);
	}
	
	protected static function getRequestIp(){
		return self::getAppInstance()->request->getIp();
	}
	
}	