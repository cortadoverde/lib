<?php

namespace Ctv\Autoloader;

Class Singleton 
{
	static private $instances;
	
	public function debug(){
		print_r(self::$instances);
	}
	public function get($class, $args = false, $autoload = true){
		if(!isset(self::$instances[$class]))
			if($autoload === true)
			 return self::$instances[$class] = self::create($class, $args);

		return self::$instances[$class];
		
	}

	static public function create($classname, $args = false)
	{
		if(!empty($classname)) { 
			$reflection = new \ReflectionClass($classname);
	     
	        if($reflection->isInstantiable()) {
	        	return new $classname($args);
	        } else {
	             return call_user_func(array($classname,"getInstance"));
	        }
        }
	}


}