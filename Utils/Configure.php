<?php

namespace Ctv\Utils;

class Configure {
	
	static private $instance = null;

	static private $collection = array();

	private function __construct(){

	}

	public function getInstance()
	{
		if(is_null(self::$instance)) {
			self::$instance = new Configure;
		}
		return self::$instance;
	}

	public static function set($key, $value)
	{
		self::$collection[$key] = $value;
	}

	public static function get($key = null)
	{
		if($key == null) {
			return self::$collection;
		}
		return self::_find($key);
		
	}

	private function _find($key)
	{
		if(empty(self::$collection)) {
			return false;
		}

		$returnArray = self::$collection;

		if(is_string($key) || is_numeric($key)) {
			$parts = explode('.', $key);
		} else {
			$parts = $key;
		}

		foreach($parts AS $index) {
			
			if(isset($returnArray[$index])) {
				$returnArray =& $returnArray[$index];
			} else {
				return false;
			}
		}
		return $returnArray;
	}

}