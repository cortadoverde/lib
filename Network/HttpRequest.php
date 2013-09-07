<?php

namespace Ctv\Network;

use Ctv\Network\Request as Request;

class HttpRequest extens Request
{
	public $context;

	private $header;

	private $request;

	public function __construct()
	{
		parent::__construct();
		
		//$this->context = \Ctv\Route\Context();
		//$this->content->type = __CLASS__;
		
		$this->initialize();
	}

	public function initialize()
	{
		// Get Headers
	}


}