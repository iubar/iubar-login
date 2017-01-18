<?php

namespace Iubar\Login\Interfaces;

interface IExternalModel {
	
	public static function getLoginUrl();

	public static function loginFromJs();

}