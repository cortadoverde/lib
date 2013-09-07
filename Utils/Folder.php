<?php

namespace Ctv\Utils;

class Folder
{
	/**
	 * Manejo de directorios
	 */
	
	/**
	 * Path de la carpeta
	 * @var string
	 */
	public $path = null;

	/**
	 * Permisos para sistemas unix no tiene efecto en windor
	 * @var integer
	 */
	public $mode = 0755;

	/**
	 * Constructor
	 * @param string $path   path de la carpeta a crear
	 * @param boolean $create Si esta en true se creara la carpeta en caso de no existir
	 * @param boolean $mode   si se define un valor se creara con este mode
	 */
	public function __construct( $path = false, $create = false, $mode = false ) 
	{
		if(empty($path)) {
			$path = ROOT . 'tmp';
		}

		if($mode) {
			$this->mode = $mode;
		}


		if( !file_exists($path) && $create === true ) {
			$this->create( $path, $this->mode );
		}

		if( !self::isAbsolute($path) ) {
			$path = realpath($path);
		}

		if( !empty($path) ) {
			$this->cd( $path );
		}
	}

	/**
	 * Devuelve el path actual
	 */
	public function pwd() 
	{
		return $this->path;
	}

	/**
	 * Cambia el path
	 * @param  string $path nuevo path
	 * @return mixed       path | false en caso de no ser un directorio
	 */
	public function cd( $path )
	{
		$path = $this->realpath( $path );

		if( is_dir($path) ) {
			return $this->path = $path;
		}

		return false;
	}

	/**
	 * Devuelve un listado con el contenido del directorio actual
	 * @param  boolean $sort       devuelve los resultados de forma ordena
	 * @param  array|boolean $exceptions datos a excluir del resultado
	 * @param  boolean $fullPath   si es true devuelve el path completo
	 * @return mixed              Contenido del directorio en un array o un error dependiendo del caso
	 */
	public function ls( $sort = true, $exceptions = false, $fullPath = false)
	{
		// Colecciones	
		$dirs = $files = array();

		if( !$this->pwd() ) {
			return array($dirs,$files);
		}

		if( is_array($exceptions) ) {
			$exceptions = array_flip($exceptions);
		}

		$skipeHidden = isset($exceptions['.']) || $exceptions === true;

		try {
			$iterator = new \DirectoryIterator( $this->path );
		} catch (Exception $e) {
			return array($dirs, $files);
		}

		foreach($iterator as $item) {
			// http://www.php.net/manual/es/directoryiterator.isdot.php
			// Determina si el elemento actual DirectoryIterator es '.' o '..'
			if( $item->isDot() ) {
				continue;
			}

			$name = $item->getFileName();

			if( $skipeHidden && $name[0] === '.' || isset($exceptions[$name]) ) {
				continue;
			}

			if( $fullPath ) {
				$name = $item->getPathName();
			}

			if( $item->isDir() ) {
				$dirs[] = $name;
			} else {
				$files[] = $name;
			}

		}

		if( $sort ) {
			sort($dirs);
			sort($files);
		}

		return array($dirs, $files);
	}

	/**
	 * Devuelve un array con las coincidencias del patron
	 * @param  string  $pattern expresion regular para el nombre de archivos
	 * @param  boolean $sort    parametro que se utiliza para ls()
	 * @return array            resultado de la busqueda	
	 */
	public function find( $pattern = '.*', $sort = false ) 
	{
		list($dirs, $files) = $this->ls($sort);
		$results = preg_grep( '/^' . $pattern . '$/i', $files );
		return ($results) ? array_values( $results ) : array();
	}

	public function findRecursive( $pattern = '.*', $sort = false )
	{
		if( !$this->pwd() ) {
			return array();
		}
		// Guardo el valor actual del path antes de hacer la recursividad
		// para que el objeto siga en la posicion inicial
		$beforePath = $this->path;
		// llamo a una funcion que se encargara de recorrer los directorios
		$out = $this->_findRecursive( $pattern, $sort );
		// vuelvo al path original
		$this->cd($beforePath);
		return $out;
	}

	private function _findRecursive( $pattern, $sort )
	{
		list($dirs, $files) = $this->ls($sort);

		$collection = array();

		foreach($files AS $file) {
			if( preg_match( '/^' . $pattern . '$/i', $file ) ) {
				$collection[] = self::addPathElement($this->path, $file);
			}
		}

		$start = $this->path;

		foreach( $dirs AS $dir ) {
			$this->cd( self::addPathElement($start, $dir) );
			$collection = array_merge( $collection, $this->findRecursive($pattern, $sort) );
		}

		return $collection;
	}

	// Funciones estaticas
	public static function isWindowsPath( $path ) 
	{
		return (preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\');
	}

	public static function isAbsolute($path) 
	{
			return !empty($path) && ($path[0] === '/' || preg_match('/^[A-Z]:\\\\/i', $path) || substr($path, 0, 2) === '\\\\');
	}

	public static function normalizePath($path) 
	{
			return self::correctSlashFor($path);
	}

	public static function correctSlashFor($path) 
	{
		return ( self::isWindowsPath($path) ) ? '\\' : '/';
	}
	
	/**
	 * Agrega el slash al final del path dependiendo el tipo de path que sea
	 * @param  [type] $path [description]
	 * @return [type]       [description]
	 */
	public static function slashTerm($path) 
	{
		if (self::isSlashTerm($path)) {
			return $path;
		}
		return $path . self::correctSlashFor($path);
	}

	/**
	 * Agrega el elemento al path ej: si path es /tmp/ y el elemento model la funcion
	 * devuelve /tmp/model
	 * 
	 * @param string $path    directorio base
	 * @param string $element elemento a concatenar
	 */
	public static function addPathElement($path, $element) {
		return rtrim($path, DS) . DS . $element;
	}

	/**
	 * Devuelve si un archivo esta en el directorio
	 *
	 * ````
	 * 	  $Folder = new Folder('/var/www/sitio/cache/model');
	 *	  $result = $Folder->inPath('/var/www/sitio/cache');
	 *	  // $result = true, /var/www/sitio/cache/model/ esta dentro de /var/www/sitio/cache/
	 *
	 *	  $result = $Folder->inPath(/var/www/sitio/cache/model/fields/, true);
	 *	  // $result = true, /var/www/sitio/cache/model/fields/ esta dentro de  /var/www/sitio/cache/model/
	 * ````	  
	 * @param  string  $path    path a comparar
	 * @param  boolean $reverse reverse search
	 * @return boolean           
	 */
	public function inPath($path = '', $reverse = false) {
		
		$dir     = self::slashTerm($path);
		$current = self::slashTerm($this->pwd());

		$directory  = ( !$reverse ) ? $current 	: $dir;
		$match 		= ( !$reverse ) ? $dir 		: $current;
		
		return (bool) preg_match('/^(.*)' . preg_quote($match, '/') . '(.*)/', $directory);
	}

	public function chmod( $path, $mode = false, $recursive = true, $exceptions = array() )
	{
		$errors = array();

		if( $mode ) {
			$mode = $this->mode;
		}

		if( $recursive === false && is_dir($path) ) {
			if( @chmod($path, intval($mode, 8)) ) {
				return true;
			}
			return false;
		}

		if( is_dir($path) ) {
			$paths = $this->tree( $path );

			foreach( $paths as $type ) {
				foreach( $type AS $fullpath ) {
					$check = explode( DS, $fullpath );
					$count = count($check);

					if( in_array($check[$count - 1], $exceptions) ) {
						continue;
					} 

					if(! @chmod($fullpath, intval($mode, 8)) ) {
						$errors[] = true;
					}
				}
			}

			if( empty($errors) ) {
				return true;
			}
		}

		return false;
	}

	public function tree( $path = null, $exceptions = false, $type = null)
	{
		
		if (!$path) {
			$path = $this->path;
		}
		$files = array();
		$directories = array($path);

		if (is_array($exceptions)) {
			$exceptions = array_flip($exceptions);
		}

		$skipHidden = false;

		if ($exceptions === true) {
			$skipHidden = true;
		} elseif (isset($exceptions['.'])) {
			$skipHidden = true;
			unset($exceptions['.']);
		}

		try {
			$directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::KEY_AS_PATHNAME | \RecursiveDirectoryIterator::CURRENT_AS_SELF);
			$iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);
		} catch (Exception $e) {
			if ($type === null) {
				return array(array(), array());
			}
			return array();
		}

		foreach ($iterator as $itemPath => $fsIterator) {
			if ($skipHidden) {
				$subPathName = $fsIterator->getSubPathname();
				if ($subPathName{0} === '.' || strpos($subPathName, DS . '.') !== false) {
					continue;
				}
			}
	
			$item = $fsIterator->current();
	
			if (!empty($exceptions) && isset($exceptions[$item->getFilename()])) {
				continue;
			}

			if ($item->isFile()) {
				$files[] = $itemPath;
			} elseif ($item->isDir() && !$item->isDot()) {
				$directories[] = $itemPath;
			}
		}
	
		if ($type === null) {
			return array($directories, $files);
		}

		if ($type === 'dir') {
			return $directories;
		}
		return $files;	
	}

	public function create( $pathname, $mode = false ) 
	{
		if ( is_dir($pathname) || empty($pathname) ) {
			return true;
		}

		if ( !$mode ) {
			$mode = $this->mode;
		}

		if ( is_file($pathname) ) {
			return false;
		}

		$pathname = rtrim($pathname, DS);
		$nextPathname = substr( $pathname, 0, strrpos($pathname, DS) );

		if ($this->create( $nextPathname, $mode )) {
			if ( !file_exists( $pathname )) {
				$old = umask(0);
				if ( mkdir( $pathname, $mode ) ) {
					umask($old);
					return true;
				}
				umask($old);
				return false;
			}
		}

		return false;
	}

	public function dirsize() 
	{
		
		$size = 0;
		$directory = self::slashTerm($this->path);
		$stack = array($directory);
		$count = count($stack);
		
		for ($i = 0, $j = $count; $i < $j; ++$i) {
			
			if (is_file($stack[$i])) {
				
				$size += filesize($stack[$i]);

			} elseif ( is_dir($stack[$i]) ) {
				
				$dir = dir($stack[$i]);
		
				if ($dir) {
					while (false !== ($entry = $dir->read())) {
						if ($entry === '.' || $entry === '..') {
							continue;
						}
						
						$add = $stack[$i] . $entry;
	
							if (is_dir($stack[$i] . $entry)) {
								$add = self::slashTerm($add);
							}
							
							$stack[] = $add;
					}
					$dir->close();
				}
			}
			
			$j = count($stack);
		}
		
		return $size;
	}

	public function delete($path = null) 
	{
		$errors = array();

		if (!$path) {
			$path = $this->pwd();
		}

		if (!$path) {
			return null;
		}

		$path = self::slashTerm($path);
		
		if ( is_dir($path) ) {
			try {
				$directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::CURRENT_AS_SELF);
				$iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
			} catch (Exception $e) {
				return false;
			}

			foreach ($iterator as $item) {
				$filePath = $item->getPathname();
				if ($item->isFile() || $item->isLink()) {
					if ( ! @unlink($filePath)) {
						$errors[] = $filePath;
					}

				} elseif ($item->isDir() && !$item->isDot()) {
					if ( ! @rmdir($filePath)) {
						return false;
					}
				}
			}

			$path = rtrim($path, DS);
			
			if ( ! @rmdir($path)) {
				return false;
			}
		}

		return true;
	}

	public function realpath($path) 
	{
		$path = str_replace('/', DS, trim($path));
		if ( strpos($path, '..') === false ) {
			if ( !self::isAbsolute($path) ) {
				$path = self::addPathElement($this->path, $path);
			}
			return $path;
		}

		$parts = explode(DS, $path);
		$newparts = array();
		$newpath = '';
		
		if ($path[0] === DS) {
			$newpath = DS;
		}

		while (( $part = array_shift($parts)) !== null ) {
			if ($part === '.' || $part === '') {
				continue;
			}
			
			if ($part === '..') {
				if (!empty($newparts)) {
					array_pop($newparts);
					continue;
				}
				
				return false;
			}

			$newparts[] = $part;
		}

		$newpath .= implode(DS, $newparts);

		return self::slashTerm($newpath);
	}		

	public static function isSlashTerm($path) {
		$lastChar = $path[strlen($path) - 1];
		return $lastChar === '/' || $lastChar === '\\';
	}
}