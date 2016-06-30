<?php

namespace Iubar\Login\Services;

class Config {

    public static function get($key){
        \Slim\Slim::getInstance()->config($key);
    }
}