<?php

namespace Ctv\Database;

use Ctv\Utils\Configure as Configure;
//use RedBean_Facade as R;

class Connection 
{
	static public $instance = null;

	static public $driver = null;

	static private $isConnected = false;

	static private $initialized = false;

	static public function getInstance( $connectionData = false)
	{
		if(!self::$instance)
			self::$instance = new self($connectionData);

		return self::$instance;

	}

	private function __construct($connectionData = false)
	{
		self::$initialized = true;
		
		//self::$driver = R::setup 
		self::connect($connectionData);
	}

	static function connect($_db = false)
	{
		
		if(!$_db) {
			$_db = Configure::get('db');
		}

		if( ! self::$initialized ) {
			self::getInstance();
		}

		if( ! self::$isConnected ) {

			if( \RedBean_Facade::setup(sprintf('mysql:host=%s;dbname=%s',$_db['host'],$_db['db']), $_db['user'], $_db['password']) ) {
				return self::$isConnected = true;
			}

			return self::$isConnected = false;	
		}

		return true;
		
	}

	static public function status()
	{
		return ( (!self::$isConnected) ? 'no ' : '' ) . 'esta conectado al servidor';
	}

	static function disconnect()
	{
		if( ! self::$initialized ) {
			return true;
		} 
		self::$isConnected = false;
		\RedBean_Facade::close();
	}

	public function __invoke()
	{
		$args = func_get_args();
		$method = array_shift($args);

		if( is_callable(array('\\RedBean_Facade',$method))){
			call_user_func_array(array('\\RedBean_Facade',$method), $args);
		}
	}
}