<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
 
use Iubar\Login\Services\ApiKey;

class RoboFile extends \Robo\Tasks {
	
	private $db_host = '192.168.0.121';
	private $db_user = 'phpapp';
	private $db_pass = 'phpapp';
	private $db_name = 'login';

	private $dest_root_path = __DIR__ . '/..';
	
	private function conn(){
		// A note about MySQL server configuration
		// You may edit the /etc/mysql/my.cnf file to configure the basic settings such as TCP/IP port, IP address binding, and other options. However, The MySQL database server configuration file on the Ubuntu 16.04 LTS is located at /etc/mysql/mysql.conf.d/mysqld.cnf and one can edit using a text editor such as vi or nano:
		// $ sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
		// Then comment the line: 
		//		bind-address           = 127.0.0.1
		
		// How do I start MySQL server?
		// sudo systemctl start mysql.service
		
		// How do I create a new MySQL server database and user account?
		// CREATE DATABASE DATABASE-NAME-HERE;
		// GRANT ALL ON DATABASE-NAME-HERE.* TO 'DATABASE-USERNAME-HERE' IDENTIFIED BY 'DATABASE-PASSWORD-HERE';
		// 
		// $ mysql -u root -p
		// SHOW DATABASES;
		// CREATE USER 'phpapp'@'%' IDENTIFIED BY 'phpapp';  oppure per maggior sicurezza CREATE USER 'phpapp'@'localhost' IDENTIFIED BY 'phpapp';
		// CREATE DATABASE login;		
		// GRANT ALL ON login.* TO 'phpapp' IDENTIFIED BY 'phpapp';
		// FLUSH PRIVILEGES;
		// EXIT;
		// $ sudo systemctl restart mysql.service
		
		// How do I reset the mysql root account password?
		// $ sudo dpkg-reconfigure mysql-server
		
		$this->say('---------------------------------');
		$this->say('Mysql host: ' . $this->db_host);
		$this->say('User: ' . $this->db_user);
		$this->say('Db name: ' . $this->db_name);
		
		$charset = 'charset=utf8mb4';
		$db_dsn = 'mysql:host=' . $this->db_host . ';port=3306;dbname=' . $this->db_name . ';' . $charset;
		$this->say('Dsn: ' . $db_dsn);
		
		try{				
			$this->dbh = new PDO(
				$db_dsn,
				$this->db_user,
				$this->db_pass,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
			// echo json_encode(array(	'outcome' => true));
		}catch(\PDOException $ex){
			$this->say(json_encode(array(
				'outcome' => false, 
				'message' => 'Unable to connect',
				'detail' => $ex->getMessage())
			));
			die('Quit' . PHP_EOL);
		}		
	}
			
    public function pumpData(){
		$this->db_pass = $this->askHidden('Db Password');
		$this->conn();
		$sql = file_get_contents('../db/sql/db.sql');
		$count = $this->dbh->exec($sql);
		$this->say('Count: ' . $count);
	}
	
    public function testDbConn(){
		$this->db_host = $this->askDefault('Mysql host', $this->db_host);
		$this->db_user = $this->askDefault('Db User', $this->db_user);
		// $this->db_pass = $this->askHidden('Sb Password');
		$this->db_pass = $this->askDefault('Db Password', $this->db_pass);
		$this->db_name = $this->askDefault('Db name', $this->db_name);		
		$this->conn();				
    }
	
	public function createApiKey(){
	    // TODO: 1) includere i vendors 2) instanziare slim altrimenti non è possibile eseguire le query
		$api_key = ApiKey::create();
		$this->say($api_key);
	}

    public function createSql() {
        $db_folder = __DIR__ . '/..';
        $mwb_file = $db_folder . '/db/er/auth.mwb';
        $output_file = $db_folder . '/db/sql/cancellami.sql';
        $cmd = "mwb2sql.bat $mwb_file $output_file";        
        $this->taskExec($cmd)->dir(__DIR__ )->run();
    }
    
    public function assets() {
         
        $templates_from = __DIR__ . '/../../iubar-login/templates';
        $templates_to = $this->dest_root_path . '/templates';
    
        $css_from = __DIR__ . '/../../iubar-login/css';
        $css_to = $this->dest_root_path . '/public/css';
    
        $js_from = __DIR__ . '/../../iubar-login/js';
        $js_to = $this->dest_root_path . '/public/js';
    
        $img_from = __DIR__ . '/../../iubar-login/img';
        $img_to = $this->dest_root_path . '/public/img';
    
        $config_from = __DIR__ . '/../../iubar-login/config';
        $config_to = $this->dest_root_path . '/config';
    
        if( !is_dir($templates_from) || !is_dir($templates_to) ){
            throw new RuntimeException("Wrong templates path or missing folder");
        }
        if( !is_dir($css_from) || !is_dir($css_to) ){
            throw new RuntimeException("Wrong css path or missing folder");
        }
        if( !is_dir($js_from) || !is_dir($js_to) ){
            throw new RuntimeException("Wrong js path or missing folder");
        }
        if( !is_dir($img_from) || !is_dir($img_to) ){
            throw new RuntimeException("Wrong img path or missing folder");
        }
        if( !is_dir($config_from) || !is_dir($config_to) ){
            throw new RuntimeException("Wrong config path or missing folder");
        }
    
        $this->taskCopyDir([$templates_from => $templates_to])->run();
        $this->taskCopyDir([$css_from => $css_to])->run();
        $this->taskCopyDir([$js_from =>  $js_to])->run();
        $this->taskCopyDir([$img_from => $img_to])->run();
        $this->taskCopyDir([$config_from => $config_to])->run();
    
        // Altri file da copiare
    
        $files = array('/../../iubar-login/.bowerrc', '/../../iubar-login/bower.json');
        foreach($files as $file){
            $path_parts = pathinfo($file);
            $basename =  $path_parts['basename'];
            $newfile = $this->dest_root_path . '/' . $basename;
            if (!copy($file, $newfile)) {
                throw new RuntimeException("Failed to copy $file");
            }
        }
    
        $this->taskMinify($css_to)->run();
    }
    
    /**
     * Se non viene specificato il parametro --config.directory
     * verrà utilizzata la configurazione dichiarata nel file .bowercc
     */
    public function bower_update(){
        // prefer dist with custom path
        $this->taskBowerUpdate(__DIR__ . '/..')
        ->option('--config.directory', $this->dest_root_path . '/public/bower_components')
        // ->noDev()
        ->run();
    }
    
    /**
     * Se non viene specificato il parametro --config.directory
     * verrà utilizzata la configurazione dichiarata nel file .bowercc
     */
    public function bower_install(){
        // simple execution
        // prefer dist with custom path
        $this->taskBowerInstall(__DIR__ . '/..')
        ->option('--config.directory', $this->dest_root_path . '/public/bower_components')
        // ->noDev()
        ->run();
    }
    
    
}
