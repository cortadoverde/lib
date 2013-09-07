<?php

namespace Ctv\Act;

Class Renderizable extends Act
{
	public function __construct($data)
	{
		$this->context = $data;
	}
}