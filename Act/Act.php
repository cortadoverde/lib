<?php

/**
 * Comportamientos
 * @todo  Implementar
 *
 * 
 **/
namespace Ctv\Act;

class Act {
	
	protected $context;

	protected $events;

	static protected $collection = array();

	public function __set($key, $value)
	{
		self::$collection[$key] = $value;
	}

	public function __get($key)
	{
		return (isset(self::$collection[$key])) ? self::$collection[$key] : false;
	}

	public function initialize()
	{
		if(!empty($this->events)) {
			foreach($this->events AS $Event) {
				if( $Event instanceof \Ctv\Event\Handle ) {
					$Event->bind();
				}
			}
		}
	}
}

