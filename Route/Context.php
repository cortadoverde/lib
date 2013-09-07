<?php

namespace Ctv\Route;

use Ctv\Utils\SysArray as SysArray;

class Context implements \ArrayAccess, \IteratorAggregate{

    private $id;

    protected $properties;

    protected static $enviroment;

    private $store;

    private function __construct( $settings = null )
    {
        if($settings) {
            $this->properties = $settings;
        } else {
            $env = array();

            $env['REQUEST_METHOD']  = $_SERVER['REQUEST_METHOD'];
            $env['REMOTE_ADDR']     = $_SERVER['REMOTE_ADDR'];
            $env['REQUEST_TYPE']    = 'HttpRequest';
            
            /**
             * Application paths
             *
             * This derives two paths: SCRIPT_NAME and PATH_INFO. The SCRIPT_NAME
             * is the real, physical path to the application, be it in the root
             * directory or a subdirectory of the public document root. The PATH_INFO is the
             * virtual path to the requested resource within the application context.
             *
             * With htaccess, the SCRIPT_NAME will be an absolute path (without file name);
             * if not using htaccess, it will also include the file name. If it is "/",
             * it is set to an empty string (since it cannot have a trailing slash).
             *
             * The PATH_INFO will be an absolute path with a leading slash; this will be
             * used for application routing.
             */
            
            

            if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
                $env['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME']; //Without URL rewrite
            } else {
                $env['SCRIPT_NAME'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']) ); //With URL rewrite
            }


            $env['PATH_INFO'] = substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($env['SCRIPT_NAME']));
            if (strpos($env['PATH_INFO'], '?') !== false) {
                $env['PATH_INFO'] = substr_replace($env['PATH_INFO'], '', strpos($env['PATH_INFO'], '?')); //query string is not removed automatically
            }
            $env['SCRIPT_NAME'] = rtrim($env['SCRIPT_NAME'], '/');
            $env['PATH_INFO'] = '/' . ltrim($env['PATH_INFO'], '/');

            //The portion of the request URI following the '?'
            $env['QUERY_STRING'] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

            //Name of server host that is running the script
            $env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

            //Number of server port that is running the script
            $env['SERVER_PORT'] = $_SERVER['SERVER_PORT'];

            //HTTP request headers
            $specialHeaders = array('CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE');
            foreach ($_SERVER as $key => $value) {
                $value = is_string($value) ? trim($value) : $value;
                if (strpos($key, 'HTTP_') === 0) {
                    $env[substr($key, 5)] = $value;
                } elseif (strpos($key, 'X_') === 0 || in_array($key, $specialHeaders)) {
                    $env[$key] = $value;
                }
            }

            //Is the application running under HTTPS or HTTP protocol?
            $env['URL_SCHEME'] = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';

            //Input stream (readable one time only; not available for mutipart/form-data requests)
            $rawInput = @file_get_contents('php://input');
            if (!$rawInput) {
                $rawInput = '';
            }
            $env['System.Input'] = $rawInput;

            //Error stream
            $env['System.Errors'] = fopen('php://stderr', 'w');

            $this->properties = $env;
        }
    }

    public static function getInstance()
    {
        if( is_null(self::$enviroment) ) {
            self::$enviroment = new self();
        }
        return self::$enviroment;
    }

    /**
     * Array Access: Offset Exists
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * Array Access: Offset Get
     */
    public function offsetGet($offset)
    {
        if (isset($this->properties[$offset])) {
            return $this->properties[$offset];
        } else {
            return null;
        }
    }

    /**
     * Array Access: Offset Set
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Array Access: Offset Unset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * IteratorAggregate
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->properties);
    }

    public function __set($name, $value)
    {
        $this->store[$name] = $value;
        return true;
    }

    public function __get($name)
    {
        if(isset($this->store[$name]))//if($value = SysArray::xpath($name, self::$store))
            return $this->store[$name];
        return false;
    }

    public function __isset($name)
    {
        return isset($this->store[$name]);
    }

    public function __unset($name)
    {
        unset($this->store['nane']);
    }
   
    public function __toString()
    {
        return outformat(array_merge(
            array(
                'sysetem'   => $this->properties
            ),
            array(
                'user'      => $this->store
            )
        ), 'var_export');
    }
}