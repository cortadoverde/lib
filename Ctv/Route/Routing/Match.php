<?php

namespace Ctv\Route\Routing;

Class Match 
{
	private $settings = array();

	public function __construct($settings)
	{
		$this->settings = $settings;
	}

	public function callback($matches)
	{
		
		if (isset($this->settings['conditions'][ $matches[1] ])) {
			return '(?P<' . $matches[1] . '>' . $this->settings['conditions'][ $matches[1] ] . ')';
		}
		if (substr($matches[0], -1) === '+') {
            $this->paramNamesPath[ $matches[1] ] = 1;

            return '(?P<' . $matches[]1 . '>.+)';
        }
        return '(?P<' . $matches[1] . '>[^/]+)';
	}
}