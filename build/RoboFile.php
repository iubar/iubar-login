<?php

class RoboFile extends \Robo\Tasks {

    
    function sql() {
        // http://apidocjs.com/
        $path = "C:/Users/Daniele/workspace_php/php-presenze/www";
        $mwb_file = __DIR__ . '/../' . 'db/er/auth.mwb';
        $output_file = __DIR__ . '/../' . 'db/sql/cacenllami.sql';
        $cmd = "mwb2sql.bat $mwb_file $output_file";
        
        $this->taskExec($cmd)->dir(__DIR__ )->run();
    }
}                   