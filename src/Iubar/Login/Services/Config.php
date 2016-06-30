<?php

namespace Iubar\Login\Services;

class Config {

    public static function get($key){
        return \Slim\Slim::getInstance()->config($key);
    }
}