<?php

namespace Iubar\Login\Core;

use Doctrine\ORM\EntityManager;

class DbResource
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private static $entityManager = null;

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEntityManager()
    {
        return self::$entityManager;
    }
    
    public static function setEntityManager($entityManager){
    	self::$entityManager = $entityManager;
    }

}