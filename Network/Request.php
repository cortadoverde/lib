<?php

namespace Ctv\Network;

class Request
{
	protected $method;

	protected $data;

	protected $allowMethods = array('GET', 'POST', 'PUT', 'DELETE');

	public function __construct()
	{
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->_getData($this->method);
	}

	protected function _getData($method = 'GET')
	{
		$data = null;
		if(in_array($method, $this->allowMethods)) {
			switch($method) {
				case 'POST':
					$data = $_POST;
					break;
				case 'PUT':
				case 'DELETE':
					parse_str(file_get_contents("php://input"),$data);
					break;
				default:
					$data = $_GET;
					break;
			}	
		}

		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public function __toString()
	{
		// echo '<pre>';
		return print_r($this->data, true);
		// echo '</pre>';
	}

}