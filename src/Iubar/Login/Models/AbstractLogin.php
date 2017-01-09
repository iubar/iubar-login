<?php

namespace Iubar\Login\Models;

abstract class AbstractLogin {

	static $app = null;
	
	public static function getAppInstance(){
		if(self::$app == null){
			self::$app =  \Slim\Slim::getInstance();
		}
		return self::$app;
	}
	
	public static function getLogger(){
		return self::getAppInstance()->log;
	}
	
	public static function config($key){
		return self::getAppInstance()->config($key);
	}
	
	protected static function getRequestIp(){
		return self::getAppInstance()->request->getIp();
	}
	
}	