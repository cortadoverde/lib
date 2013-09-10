<?php

namespace Ctv\Route;

/**
 * Router principal
 *
 * Obtine los parametros a travez del request
 *
 * ## Parametros por url:
 * Son los parametros que se capturan dentro del request_uri que contienen dos puntos (:) 
 * la expresion valida para estos parametos es \/(.+):(.+)\/?, dejando el primer patron como el 
 * key y el segundo como el value
 * 
 * /category:5
 * 
 * 		(
 * 			[category] => 5
 * 		)
 *
 * /category:5/zone:1/p:1
 *
 * 		(
 * 			[category] 	=> 5
 * 			[zone]		=> 1
 * 			[p]			
 * 		)
 * 		
 * @author Pablo Adrian Samudia <p.a.samu@gmail.com>
 * @version  1.0 
 */
class Route {
	
	/**
	 * Request_uri sin el nombre de la App
	 * @var string
	 * @example /api/events/date:2013-01-01/p:5 => [URI] -> /events/date:2013-01-01/p:5 
	 * @access  public
	 */
	public $URI = NULL;

	/**
	 * Url sin {# parametros por url }
	 * 
	 * @var string
	 * @access  private
	 */
	private $url = null;

	/**
	 * Parametros capturados por el Route
	 * @var array
	 * @access  private
	 */
	private $params = array();

	/**
	 * Expresiones para capturar
	 * @todo  Aplicarla en mapUrl, definir la forma de capturar los datos ej: /:controller => (?P<controller>)
	 * @var array
	 * @access  private
	 */
	private $matches = array();

	/**
	 * Collecion de nombre de app
	 * se utiliza con registerApp y luego es utilizado en setApp para armar la expresion
	 * @var array
	 * @access  private
	 */
	private $appMatch = array();

	/**
	 * Extensiones a capturar al final de la url, estas se utilizan
	 * para definir el tipo de Output que va a generar el dispatcher
	 * @var array
	 * @access  private
	 */
	private $mapExtensions = array('html','json', 'xml');


	public $context;

	/**
	* @var array Conditions for this route's URL parameters
	*/
    protected $conditions = array();

    /**
     * Colector de argumentos para el mapeo de urls
     * @var array
     */
    protected $arguments = array();

    /**
     * Instancia del route del sistema
     *
     * Se agrega esta opcion para mantener una unica 
     * instancia del route para el dispatcher, pero no
     * se crea un constructor privado ya que se permite
     * tener mas de un route para despachar
     * 
     * @var [type]
     */
    static public $instance = NULL;

	/**
	 * Constructor del Route
	 *
	 * Obtiene el request y define $this->URI
	 * @access  public
	 */
	public function __construct($basedir = false) 
	{
		$this->context = \Ctv\Route\Context::getInstance(array());

		$base = ($basedir === false) ? dirname($this->context['SCRIPT_NAME']) : $basedir;
		

		$this->context['BASE_URI'] = $base;		
		
		$this->params['base'] = $base;

		$request = ($base != '/') ? str_replace($base,'',$_SERVER['REQUEST_URI']) : $_SERVER['REQUEST_URI'];
		
		$this->URI = rtrim($request,'/');

	}

	/**
	 * Devuelve o setea la instancia del Route
	 * Se define por primera vez en /Config/boostrap.php
	 * 	
	 * @param  string $basedir baseUrl
	 * @return Route           Instancia del objeto Route
	 */
	static public function getInstance($basedir = false)
	{
		if(self::$instance == NULL) {
			self::$instance = new self($basedir);
		}
		return self::$instance;
	}
	/**
	 * Inicializa la captura de parametros
	 *
	 * no se es llamada en el constructor, ya que la idea es que este metodo 
	 * se llame luego de que se definan los patrones a capturar para las
	 * rutas, en caso se llamarse en el constructor capturaria los datos sin
	 * dar posibilidad que se definan reglas personalizadas
	 * 	 
	 * @return Objset Route
	 * @access public
	 */
	public function initialize()
	{
		
		$this	->setApp()
				->mapExtensions()
				->extractParams()
				->map();
		return $this;
	}

	/**
	 * Define que App debe observar en el URI
	 * @param  string $app Nombre de la App (api, wcp, flex, etc)
	 * @return Object      Route
	 */
	public function registerApp($app)
	{
		$this->appMatch[] = $app;
		return $this;
	}

	/**
	 * Agrega una expresion a capturar cuando se evaluen las rutas
	 * @param  string $reg  Expresion a definir
	 * @param  array $data parametros que va a setear cuando evalue los daots
	 * @return Object       Route
	 */
	public function match($reg, $data)
	{



		$this->matches[] = array($reg,$data);
		/**
		 * /^([Api|Wcp]+)([\w|\/]+)?$/is
		 * ^api/* => array(
		 * 		app => api
		 * 		match => array(
		 * 			* => 
		 * 		)
		 * )
		 *
		 *  ^/([Api|Wcp])+[\w|/]?$
		 *	^/events => array(
		 *		'App' => Api.
		 *		
		 *	)
		 *
		 * 
		 */
		return $this;
	}

	/**
	 * Obtiene los {# parametros por url}
	 * @return string Url sin parametros
	 */
	private function extractParams()
	{
		// Si el request no esta vacio voy a separar los datos que vengan con : ya que el orden de estos
		// datos no son extrictos
		$returnUrl = array();


		if(!empty($this->URI)) {
			$parts = explode('/', $this->URI);
			foreach($parts AS $urlMap) {
				if(preg_match('/(?P<key>.+):(?P<value>.+)/',$urlMap, $result)) {
					$this->params['params'][$result['key']] = $result['value']; 		
				} else {
					$returnUrl[] = $urlMap;
				}
			}
		}
		$this->url = (empty($returnUrl)) ? '/' : implode('/',$returnUrl);
		return $this;
	}

	/**
	 * Setea la App que se esta utilizando
	 *
	 * La expresion captura si el URI comienza con alguna App registrada
	 * ^\/(AppRegistrada)\/(.*)?$
	 *
	 * En caso de no evaluar la expresion define is_default como true y toma
	 * los datos del Configure::get('Default') => definidos en el bootstrap
	 * 
	 */
	private function setApp()
	{
		$this->params['Request'] = $this->URI;

		$regexp = '/^\/(' . implode('|', $this->appMatch) . ')\/(.*)?$/is';

		if(preg_match($regexp, $this->URI . '/', $results)) {
			$this->params['is_default'] = false;
			$this->params['App'] = ucfirst(strtolower($results[1]));
			$this->URI = (isset($results[2])) ? '/' . $results[2] : '/';
		} else {
			$this->params['is_default'] = true;
			$this->params['App'] = ucfirst(strtolower(\Ctv\Utils\Configure::get('Default.App')));
			
		}
		$this->params['URI'] = (substr($this->URI, -1) == '/') ? rtrim($this->URI,'/') : $this->URI ;

		return $this;
	}

	public function map()
	{
		$pattern = "#^/(?<controller>[\w-]+)(?:/(?<action>[\w-]+))?(?:/(?<args>[^?]+).*?|$)?#";
		$applySettings = array();
		$params = $this->params['named'] = array();

		if(!empty($this->matches)) {
			foreach ($this->matches as $match) {

				$unparsedRegExp = $match[0];
				$settings = $match[1];

				if(isset($settings['conditions'])) {
					$this->conditions = $settings['conditions'];
					unset($settings['conditions']);
				}

				if( isset($settings['asArgument'])) {
					$this->arguments = $settings['asArgument'];
					unset($settings['asArgument']);
				}

				if( isset($settings['call']) && is_callable($settings['call']) ) {
					$settings['call']($this);
				}

				$this->params['named'] = array();
				$regExp = 	preg_replace_callback('#:([\w]+)\+?#',
								array($this, 'matchCallback'),
								str_replace( ')', ')?', (string) $unparsedRegExp )
							); 

				if (substr($unparsedRegExp, -1) === '/') {
		            $regExp .= '?';
		        }
		        //echo $regExp;
		        if(!isset($this->conditions['app']) 
		        	|| 
		        	( $this->conditions['app'] == 'default' && $this->params['is_default'] )
		        	||
		        	strtolower($this->conditions['app']) == strtolower($this->params['App'])
		        ) {

		        	if(preg_match('#^' . $regExp . '$#', $this->url, $appMatches)) {
						$this->params['capture_route'] = $match[0];
						$pattern = '#^' . $regExp . '$#';
						$params = $this->params['named'];
						$applySettings = $settings;
						break;
					}
		        }
				
			}
		}
		$this->params['regExpMatch'] = $pattern;
		if(empty($params)) {
			unset($this->params['named']);
		}
		$this->mapUrl($pattern, $applySettings);
		return $this;


	}

	private function matchCallback($m)
	{
		$this->params['named'][] = $m[1];
		
		if (isset($this->conditions[ $m[1] ])) {
			return '(?P<' . $m[1] . '>' . $this->conditions[ $m[1] ] . ')';
		}
		if (substr($m[0], -1) === '+') {
            $this->paramNamesPath[ $m[1] ] = 1;

            return '(?P<' . $m[1] . '>.+)';
        }
        return '(?P<' . $m[1] . '>[^/]+)';
	}

	 function eachMatchSettings($settings, $data) 
	{
		$collection = $settings;
		foreach($settings AS $key => $value) {
			if(!is_array($value)) {
				$collection[$key] = (isset($data[str_replace(':', '', $value)])) ? $data[str_replace(':', '', $value)] : $value;  
			} else {
				$settings = &$value;
			}
		}
		return $collection;

	}

	/**
	 * Mapea la url para obtener el controlador, accion y argumentos
	 * @return Object Route
	 */
	private function mapUrl($pattern, $settings = array())
	{
		// 		 pattern /:controller/:action[/param1/param2/param3]
		// $pattern = "#^/(?<controller>[\w-]+)(?:/(?<action>[\w-]+))?(?:/(?<args>[^?]+).*?|$)?#";

		if(preg_match($pattern, $this->url, $appMatches)){

			$this->params['controller'] = (isset($appMatches['controller'])) ? $appMatches['controller'] : \Ctv\Utils\Configure::get('Default.controller');//$appMatches['controller'];
			$this->params['action']		= (isset($appMatches['action'])) ? $appMatches['action'] : \Ctv\Utils\Configure::get('Default.action');
			
			if(!empty($this->params['named'])) {
				foreach($this->params['named'] AS $n => $captureParam) {
					if(isset($appMatches[$captureParam])) {
						$this->params['named'][$captureParam] = $appMatches[$captureParam];
						unset($this->params['named'][$n]);
					}
				}
			}


			if(isset($appMatches['args'])) {
				$this->params['args'] = explode('/', $appMatches['args']);
			}

			if(!empty($this->arguments)) {
				foreach($this->arguments AS $n => $key) {
					if(isset($this->params['named'][$key])){
					$this->arguments[$n] = $this->params['named'][$key];
					unset($this->params['named'][$key]);
				} else {
					unset($this->arguments[$n]);
				}
				}
				$this->params['args'] = (empty($this->params['args'])) ? $this->arguments : array_merge($this->params['args'], $this->arguments);	
			}


		}else{
			$this->params['controller'] = \Ctv\Utils\Configure::get('Default.controller');
			$this->params['action'] = \Ctv\Utils\Configure::get('Default.action');
			 
		}	
		
		// Overload Settings
		if(!empty($settings)) {
			$this->params = array_merge($this->params, $settings);
		}

		return $this;
	}

	/**
	 * Evalua si se encuentra al finalizar el URI alguna de las extensiones
	 * guardadas en mapExtensions
	 *
	 * define el param => ext
	 *
	 * ## Ejemplo
	 * /api/events/view/1.json
	 * 		(
	 * 			[params] => (
	 * 				[ext] 			=> json
	 * 				[App] 			=> api
	 * 				[controller] 	=> events
	 * 				[action]		=> view
	 * 				[args] => (
	 * 					[0] => 1
	 * 				)
	 * 			)
	 * 		)
	 * @return Object Route
	 */
	public function mapExtensions()
	{
		if(preg_match('/\.(?P<ext>' . implode('|', $this->mapExtensions) . ')$/', rtrim($this->URI,'/'), $matchExtension) ) {
			$this->params['ext'] = $matchExtension['ext'];
			$this->URI = str_replace('.' . $matchExtension['ext'], '', $this->URI);
		}
		return $this;
	}

	/**
	 * getter de params
	 * 
	 * si no se especifica un indice devuelve todos los parametros.
	 * si se especifica un indice y existe devuelve el parametro correspondiente para el indice,
	 * pero si no existe devuelve false.
	 *
	 * por lo que puede utilizarse por ejemplo, para saber si hay argumentos en el Route, o para saber si 
	 * se capturo una extension, estos parametros no estan siempre presentes.
	 *
	 * 		<?php
	 * 			if($ext = Route::getParams('ext'))
	 * 				die('la extension es ' . $ext); // en caso de no existir el indice ext, $ext pasaria a ser false y no pasaria la condicion
	 *
	 * 			if( ( $args = Route::getParams('args') ) !== false ) {
	 * 				var_dump($args);
	 * 			}
	 * 		?>
	 * 
	 * @param  boolean $key [description]
	 * @return [type]       [description]
	 */
	public function getParams($key = false)
	{
		if(!$key)
			return $this->params;
		return (isset($this->params[$key])) ? $this->params[$key] : false;
	}

	public function setParams($key, $value)
	{
		$this->params = array_merge($this->params, $value);
	}

	public function getUrl() 
	{
		return $this->url;
	}

	public function conditions(array $conditions)
    {
        $this->conditions = array_merge($this->conditions, $conditions);

        return $this;
    }



	public function __toString()
	{
		$this->params['url_use'] = $this->url ;//rtrim(,'/');
		return outformat($this->params);
	}

	public function __call($method, $args)
	{
	// <function>
			// Aseguramos que el metodo este dentro de una lista controlable
			$allowMethods = array('store', 'getStore');
			if( in_array($method,$allowMethods) ) {
				// Almacena los datos en una collecion local
				if( $method == 'store' ) {
					// si no esta definido el store creamos un array
					if( !isset($this->store) ) 
						$this->store = array();
					// almacenamos los argumentos
					$this->store[] = $args[0];
					// devuelve el store
					return $this->store;
				}
				if( $method == 'getStore' ) {
					return $this->store;
				} 
			}
			return null;
	// </function>	
	}

	public function __invoke()
	{
	//	<function> 
			$args = func_get_args();
			// 	al menos un argumento
			if( count($args) > 0 ) {
				// si el primer argumento es un array
				if( is_array($args[0]) ) {
					return $this->store($args[0]);
				} else {
					//	si existe el metodo 
					if( is_callable($this, $args[0]) ) {
						//	quita el primer argumento y se lo asigna a method
						$method = array_shift($args);
						//	ejecuta el metodo
						return call_user_func_array(array($this, $method), $args);
					}
					// si el argumento 0 es una propiedad, devuelve su valor
					if( isset($this->{$args[0]}) ) {
						// devuelve el valor
						return $this->{$args[0]}; 
					}
				}
			}
	//	</function> 
	}

}