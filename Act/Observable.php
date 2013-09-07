<?php

namespace Ctv\Act;
/**
 * Getters => {
 *     			La interfaz define propiedades
 *     			ej: $observable
 *
 * 				$this->is(?P<interface/abstract class>observable|inicializable) = function($get, $match) {
 * 					return isset($this->{$match[interface]});
 * 				}
 *     		
 *     		}	
 */
Class Observable 
{

	static private $c = [];

	static public function __callStatic( $method, $args )
	{
		if( preg_match('/^[g|s]et$/', $method) ) {
			$num_args = count($args);

			switch ($num_args) {
				 case 0:
				default:
					return null;
					break;
				case 1:
					return self::get($args[0]);
					break;
				case 2:
					return self::set($args[0],$args[1]);
					break;
				
			}
		}

		if( preg_match('/^is(.*)', $method, $isMatch) ) {
			// is POST | is Debug
			if(isset())
		}
	}

	static public function get($k)
	{
		if(isset(self::$c[$k])){
			return self::$c[$k];
		}
	}

	static public function set($k, $v)
	{
		self::$c[$k] = $v;
	}

	public function debug()
	{
		var_export(self::$c);
	}

}
