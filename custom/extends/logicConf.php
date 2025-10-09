<?php
/*
	include_once 'custom/logic/AccessLogic.php';
	include_once 'custom/logic/AdwaresLogic.php';
	include_once 'custom/logic/NUserLogic.php';
	include_once 'custom/logic/PayLogic.php';
	include_once 'custom/logic/ReturnssLogic.php';
	include_once 'custom/logic/SalesLogic.php';
	include_once 'custom/logic/SecretAdwaresLogic.php';
	include_once 'custom/logic/TableLogic.php';
*/
	class LogicClassAutoloader {

		private $whitelist = array();
		private $logic_path = "custom/logic/";

        public function __construct() {
			$this->whitelist = array(
				'MailLogic',
				'AutoLoginLogic',
				'AccessLogic',
				'AdwaresLogic',
				'NUserLogic',
				'PayLogic',
				'ReturnssLogic',
				'SalesLogic',
				'SecretAdwaresLogic',
				'TableLogic',
			 );

            spl_autoload_register(array($this, 'loader'));
        }
        private function loader($className) {

        	if( array_search( $className, $this->whitelist  ) !== FALSE ){
	            include_once $this->logic_path.$className . '.php';
        	}
        }
    }
    $autoloader = new LogicClassAutoloader();