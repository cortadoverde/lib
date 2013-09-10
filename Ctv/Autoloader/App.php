<?php

namespace Ctv\Autoloader;

class App
{
	static public $_namespaceSeparator = '\\';
	
	static public $_fileExtension = '.php';

	static public $runtimeLoaded = array();

	static function validate($className)
	{
        return true;
		$fileName = '';
        $namespace = '';

        if (false !== ($lastNsPos = strripos($className, self::$_namespaceSeparator))) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace(self::$_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . self::$_fileExtension;


        $nsRegister = array_shift(explode(self::$_namespaceSeparator, $namespace));

        $includePath = isset(self::$runtimeLoaded[$nsRegister]) ? self::$runtimeLoaded[$nsRegister] : '';

        $realFile =  ($includePath !== null ? $includePath . DIRECTORY_SEPARATOR : '') . $fileName;

        echo $realFile;
        return file_exists($realFile);
	}
}


// Dashboard 
// Dashboard.php