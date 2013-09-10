<?php

namespace Ctv\Network;

/**
 * # Dispatcher
 *
 * ## Ctv\Network
 *
 * @author  Pablo Adrian Samudia <p.a.samu@gmail.com>
 *
 *
 * 
 *
 * 			Usar Interfaces
 * 				\Observable
 * 					-> log();
 * 					->
 * 				\Inicializable
 *     		Getters => {
 *     			La interfaz define propiedades
 *     			ej: $observable
 *
 * 				$this->is(?P<interface/abstract class>observable|inicializable) = function($get, $match) {
 * 					return isset($this->{$match[interface]});
 * 				}
 *     		
 *     		}				
 *
 * 			__invoke()
 *
 * 			$this->__invoke(Function,function(){
 * 				return Function(arguments);
 * 			})
 *
 * 			__invoke()
 * 			{ 
 * 				$args = func_get_args();
 * 				if(count($args) > 0) {
 * 					if(method_exu
 * 				}
 * 			}
 */
class Dispatcher
{
	

	public function dispatch(\Ctv\Route\Route $router, $response)
	{
			$controllerName = ucwords($router->getParams('controller'));

			$namespace = ucwords($router->getParams('App')) .'\\Controller\\' . $controllerName;

			if(\Ctv\Autoloader\App::validate($namespace)) {
				
				$reflection = new \ReflectionClass($namespace);	
			
				if($reflection->hasMethod($router->getParams('action'))){
					$method = $reflection->getMethod($router->getParams('action'));
					$controller = $reflection->newInstance($router, $response);
					if($args = $router->getParams('args')){
						$method->invokeArgs($controller,$args);
					}else {
						$method->invoke($controller);	
					}

					$this->__callOutPut($controller);

					//$this->close();

				} else {
					echo outformat($namespace, 'trace');
					echo outformat($router, 'trace');
					echo $router;
					die('Method not exists' . $namespace);
				}
			} else {
				echo $namespace . 'error 404';
			}
			die;
	}

	private function __callOutPut($controller)
	{
		$Output = new \Common\Output\Output();
		

		try{
			$Output->show($controller);
		} catch (Exception $e) {
			echo 'no se encuentra el engine de renderizacion';
			die;
		}

	}
}