<?php

namespace Ctv\Event;

class Handle 
{
	private $data = null;

	public function __construct($data = null)
	{
		$this->data = $data;
	}

	public function result()
	{
		return $this->data;
	}

}