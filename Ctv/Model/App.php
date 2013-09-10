<?php

namespace Ctv\Model;

use RedBean_Facade as R; 
use \Ctv\Utils\Configure;

Class App {

	private $config;

	public $entityManager;

	protected $name;

	protected $entity;

	protected $insertId;

	protected $limit = NULL;

	protected $qFields = '*';
	
	protected $qConditions = array();

	protected $allowFields = array();

	private $operations = array(
		'mayor' => array(
			'where' => ':field > ?'
		),
		'minor' => array(
			'where' => ':field < ?'
		)
	);



	public $rows = array();

	function __construct()
	{
		$this->connector = \Ctv\Database\Connection::getInstance();
		
	}

	protected function create()
	{
		if(empty($this->name))
			return false;
		$this->entity = R::dispense($this->name);
		return $this;
	}

	public function save($_entity = false)
	{
		
		$entity = (!$_entity) ? $this->entity : $_entity;
		
		$this->insertId = R::store($entity);
		return $this;
	}

	public function delete($_entity = false)
	{
		$entity = (!$_entity) ? $this->entity : $_entity;
		R::trash($entity);
		return $this;
	}

	public function set($data)
	{
		foreach($data As $key => $value) {
			$this->entity->{$key} = $value;
		}
		return $this;
	}

	public function get($value)
	{
		return $this->entity->{$value};
		return $this;
	}

	public function __call($method, $args)
	{
		// Si el metodo no existe creamos los patrones para las llamadas especiales
		// findBy	
		if(!method_exists($this,$method)) {
			if(preg_match('/^findBy(?P<field>.+)$/is',$method,$match)){
				$this->rows = R::$f->begin()
									->select('*')
									->from($this->name)
									->where(' ' . $match['field'] . ' = ? ')->put($args[0])
									->get();
				return $this->rows;
			}
		}
	}

	public function saveAll($data)
	{
		$collection = array();
		foreach($data AS $index => $row) {
			$collection[$index] = R::dispense($this->name);
			foreach($row AS $name => $value) {
				$collection[$index]->{$name} = $value;
			}
		}
		R::storeAll($collection);
	}

	public function select($fields)
	{
		if(!is_array($fields)) {
			$this->qFields = $fields;
		} else {
			$this->qFields = implode(', ', $fields);
		}
		return $this;
	}

	// where("title like ?", "%titulo%");
	public function where($sql, $value) {
		$this->qConditions[$sql] = $value;
		return $this;
	}

	public function _and($sql, $value)
	{
		$this->qConditions[' AND ' . $sql] = $value;
		return $this;
	}

	public function _or($sql, $value)
	{
		$this->qConditions[' OR ' . $sql] = $value;
		return $this;
	}

	public function filter()
	{
		$collection = array();
		
		// where("title like ?", "%titulo%");

		R::$f->begin()
			->select($this->qFields)
			->from($this->name);
		
		if(!empty($this->qConditions)) {
			R::$f->where(implode(' ', array_keys($this->qConditions)));
			foreach($this->qConditions AS $sql => $value){
				if($value !== false)
				R::$f->put($value);	
			}
		}
		// Limit
		if(!is_null($this->limit)) {
			R::$f->limit($this->limit);	
		}
		
		$this->rows = R::$f->get();
		return $this->rows;
	}


	public function find($params, $limit = null)
	{
		$this->select('*');
		$this->limit = $limit;
		if(!empty($params)){
			if(is_array($params)) {
				$this->where('1 = ?', 1);
				foreach($params AS $key => $value) {
				
					$mapArray = array(
						'where' => '1 = ?',
						'value' => 1
					);


					if(isset($this->fieldMap[$key])) {

						$mapArray = $this->fieldMap[$key];
							
					} elseif (isset($this->fieldMap[$key.'_'.$value])){
						$mapArray = $this->fieldMap[$key.'_'.$value];
					}else {

						if(in_array($key, $this->allowFields)) {
							
							$mapArray = array(
								'where' => $key . ' = ?',
								'value' => $value
							);	

						} else {

							if(preg_match('/(?P<field>.+)_(?P<operator>' . implode('|', array_keys($this->operations)) .')/is', $key, $match)) {
								
								$mapArray = $this->operations[$match['operator']];
								$mapArray['where'] = str_replace(':field', $match['field'], $mapArray['where']);
								
							}

						}
					}

					$op = (!isset($mapArray['op'])) ? 'and' : $mapArray['op'];
					$_value = (!isset($mapArray['value'])) ? $value : $mapArray['value'];


					$this->{"_" . $op}( $mapArray['where'], $_value );	
				}
			}else{
				if($value !== false)
					$this->wehere($params, $value);
			}
		}

		return $this->filter();
	}


}