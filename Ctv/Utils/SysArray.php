<?php

namespace Ctv\Utils;

class SysArray
{
	static function xpath($key, $returnArray)
	{
		if(empty($returnArray)) {
			return false;
		}

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