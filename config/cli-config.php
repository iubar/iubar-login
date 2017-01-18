<?php
require_once "bootstrap.php";

use Doctrine\ORM\EntityManager;

// We need to provide entityManager to the command line interface
// The CLI interface allows us to submit interact with the database
// for example to update or create the schema

$entityManager = EntityManager::create($conn, $config);

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);

 
// Any way to access the EntityManager from  your application
 

$helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($entityManager->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($entityManager)
));



