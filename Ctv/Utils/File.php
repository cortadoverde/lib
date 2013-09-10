<?php

namespace Ctv\Utils;

Class File
{
	const STATE_CLOSE  = 0; // 0x00;
	const STATE_OPEN   = 1; // 0x01;

	const STATE_FAILED = 2; // 0x10;
	
	public $path = NULL;

	public $mode = 0755;

	private $handle = false;

	public $content = NULL;

	protected $state;

	public function __construct($filename = false)
	{
		if( !$filename || strlen($filename) == 0 )
			$this->_Error('No se definio el nombre de archivo');
		$this->path = $filename;
		$this->state = self::STATE_CLOSE;
	}

	public function open($mode = 'r')
	{
		if($this->state == self::STATE_FAILED) { return false; }

		if($this->state == self::STATE_CLOSE) {
			if(!file_exists($this->path)) {
				$this->state = self::STATE_FAILED;
				$this->_Error('No existe el archivo `' .  basename($this->path).'` :: ' . $this->path . ' ');
				return false;
			}
			
			$this->handle 	= fopen($this->path, $mode);
			$this->state = self::STATE_OPEN;
		}
	} 

	/**
	 * lee hasta length bytes desde el puntero al fichero referenciado por handle. La lectura termina tan pronto como se encuentre una de las siguientes condiciones:
     *
     * length bytes han sido leídos
     * EOF (fin de fichero) es alcanzado
     * un paquete se encuentra disponible o el tiempo límite del socket se agota (para flujos de red)
     * si el flujo es leído en buffer y no representa un fichero plano, se realiza al menos una lectura de hasta un número de bytes igual al tamaño del trozo (normalmente 8192); dependiendo del la información previamente almacenada en buffer, el tamaño de la información devuelta puede ser mayor que el tamaño del trozo.
     * 
	 */
	public function read($length = false) {
		if($this->state != self::STATE_OPEN ) {  return false; }

		$this->content = fread( 
							$this->handle, 
							
							( !$length 
								?  
									filesize($this->path) 
								: 
									$lenght 
							) 
						);
		return $this->content;
	}

	public function write($data)
	{
		if($this->state != self::STATE_OPEN ) {  return false; }

		fwrite($this->handle, $data);
	}

	public function close()
	{
		if($this->state == self::STATE_CLOSE) { return true; }
		$this->state = self::STATE_CLOSE;
		fclose($this->handle);
		return true;
	}

	/**
	 * Crea un nuevo archivo con el modo asignado
	 * @param  [type]  $path      [description]
	 * @param  boolean $overwrite [description]
	 * @return [type]             [description]
	 */
	static public function create($path, $mode = 'w+' ,$overwrite = false)
	{
		if( self::exists($path) && $overwrite == false )
			return false;
		$handle = fopen($path, $mode);
		fclose($handle);

	}

	static public function exists($path)
	{
		return file_exists($path);
	}

	private function _Error($msj, $code = 1)
	{
		trigger_error($msj, E_USER_ERROR);
		throw new Exception($msj, 1);		
		echo $msj;	
	}


	public function __destruct()
	{
		if($this->state == self::STATE_OPEN) {
			$this->close();
		}
	}


}