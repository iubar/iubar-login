<?php

use Doctrine\ORM\Tools\Setup;

require_once __DIR__ . '/../vendor/autoload.php';

$paths = array(__DIR__. '/../src/Application/Models/metadata');

$config = Setup::createXMLMetadataConfiguration($paths, true);

// Set up database connection data
$conn = array(
	'driver'   => 'pdo_mysql',
	'host'     => '192.168.0.121',
	'dbname'   => 'login',
	'user'     => 'phpapp',
	'password' => 'phpapp'
);