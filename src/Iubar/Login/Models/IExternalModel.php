<?php

namespace Iubar\Login\Models;

interface IExternalModel {
	
	public static function getLoginUrl();

	public static function loginFromJs();

}