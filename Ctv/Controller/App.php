<?php

namespace Ctv\Controller;

class App {
	
	protected $_locked = false;

	private $collection = array();

	protected $route;

	protected $response;

	public function __construct($route, $response)
	{
		$this->route = $route;
		$this->response = $response;
		$this->__setResponse();
	}

	private function __setResponse()
	{
		$this->response['type'] = ($ext = $this->route->getParams('ext')) ? $ext : \Ctv\Utils\Configure::get('Default.ext'); 
	}

	public function set($key, $value)
	{
		if($this->_locked !== true) {
			$this->collection[$key] = $value;
		}

		return true;
	}

	public function get($key = false)
	{
		if(isset($this->collection[$key])) {
			return $this->collection[$key];
		}
	}

	public function getResponse()
	{
		return $this->response;
	}

	public function getRoute()
	{
		return $this->route;
	}
}