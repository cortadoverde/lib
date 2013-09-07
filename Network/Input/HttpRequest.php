<?php

namespace Ctv\Network\Input;
/**
 * HTTP - HyperText Transfer Protocol
 * 
 * It's a stateless request-response based communication protocol. 
 * It's used to send and receive data on the Web i.e., over the Internet. 
 * This protocol uses reliable TCP connections either for the transfer of data 
 * to and from clients which are Web Browsers in this case. 
 * HTTP is a stateless protocol means the HTTP Server doesn't maintain 
 * the contextual information about the clients communicating with it and hence 
 * we need to maintain sessions in case we need that feature for our Web-applications. 
 * 
 * Read more about this concept in the articles - Need for Session Tracking and Session Tracking Implementation in Servlets.
 * 
 * This protocol has three well-known versions so far: HTTP/0.9 being the first version, 
 * HTTP/1.0 came next, and now we normally use the HTTP/1.1 version. Interested in more 
 * details of these versions? Refer to the RFC2616 defined by w3.org.
 * 
 * As we just saw that HTTP is a request-response based protocol. That means the client 
 * will initiate the communication by sending a request (normally called an HTTP Request) 
 * and the HTTP Server (or Web Server) will respond back by sending a response 
 * (usually called an HTTP Response). Everytime a client needs to send the request, 
 * it first establishes a TCP reliable connection with the Web Server and then transfer the request via this connection. 
 * The same happens in case a Web Server needs to send back an HTTP Response to a client. 
 * Any of the two parties - the client or the server can prematurely stop the transfer by terminating the TCP connection. 
 * How a client can terminate the connection is pretty easy to visualize, isn't it? 
 * It can be done simply by clicking the 'Stop' button of the browser window (or by closing the browser window itself :-)).
 * 
 * Let's move on to discussing how an HTTP Request or an HTTP Response does look like? Both the Request and the Response 
 * have a pre-defined format and it should comply with that so that both the client (the Web Browser) and the server (HTTP/Web Server) 
 * can understand and communicate properly with each other.
 * 
 * 
 * ## Format of an HTTP Request ## 
 * It has three main components, which are:-
 * 		
 * - HTTP Request Method
 * 
 * 		URI, and Protocol Version - this should always be the first line of an HTTP Request. 
 * 		As it's quite evident from the name itself, it contains the HTTP Request method being used for that particular request, 
 * 		the URI, and the HTTP protocol name with the version being used. It may look like 'GET /servlet/jspName.jsp HTTP/1.1' 
 * 		where the request method being used is 'GET', the URI is '/servlet/jspName.jsp', and the protocol (with version) is 'HTTP/1.1'.
 * 
 * - HTTP Request Headers 
 * 		
 * 		this section of an HTTP Request contains the request headers, which are used to communicate information about the client environment. 
 * 		Few of these headers are: Content-Type, User-Agent, Accept-Encoding, Content-Length, Accept-Language, Host, etc. 
 * 		Very obvious to understand what info do these headers carry, isn't it? The names are quite self-explanatory.
 * 		
 * - HTTP Request Body 
 * 
 * 		this part contains the actual request being sent to the HTTP Server. The HTTP Request Header and Body are separated by a blank line 
 * 		(CRLF sequence, where CR means Carriage Return and LF means Line Feed). This blank line is a mandatory part of a valid HTTP Request.
 * 
 * 
 * ## Format of an HTTP Response
 * Similar to an HTTP Request, an HTTP Response also has three main components, which are:-
 * 
 * - Protocol/Version, Status Code, and its Description  
 * 		
 * 		the very first line of a valid HTTP Response is consists of the protocol name, it's version, status code of the request, 
 * 		and a short description of the status code. A status code of 200 means the processing of request was successful and the description 
 * 		in this case will be 'OK'. Similarly, a status code of '404' means the file requested was not found at the HTTP Server at the expected 
 * 		location and the description in this case is 'File Not Found'.
 * 		
 * - HTTP Response Headers 
 * 		
 * 		similar to HTTP Request Headers, HTTP Response Headers also contain useful information. The only difference is that HTTP Request Headers 
 * 		contain information about the environment of the client machine whereas HTTP Response Headers contain information about the environment 
 * 		of the server machine. This is easy to understand as HTTP Requests are formed at the client machine whereas HTTP Responses 
 * 		are formed at the server machine. Few of these HTTP Response headers are: Server, Content-Type, Last-Modified, Content-Length, etc.
 * 		
 * - HTTP Response Body - this the actual response which is rendered in the client window (the browser window). The content of the body will be HTML code. Similar to HTTP Request, in this case also the Body and the Headers components are separated by a mandatory blank line (CRLF sequence).
 */
Class HttpRequest 
{

	private $allowMethods = array('GET','PUT', 'POST', 'DELETE');

	private $method = 'GET';

	private $header = array();

	private $body;

	private $data;

	private $url;

	// use Ctv\Network\Input\HttpRequest As Request;
	// $Request = new Request($url);
	// $Request->header(<params[,...]>);
	// $Request->[GET|POST|DELETE|PUT](<params[,...]>);
	// $Request->dispatch();

	public function __construct($url = '') 
	{
		if(!empty($url))
			$this->url($url);
	}

	public function url( $url = false, $append = true) {
		if(!$url)
			return $this->url;
		$this->url = ($append) ? $this->url . $url : $url;
		return $this;
	}

	public function header()
	{
		switch( count( func_num_args() ) ) {
			case 0 : 
				return $this->header;
				break;
			case 1 : 
				$this->header['_params_'][] = func_get_arg(0);
				break;
			case 2 :
				$this->header[func_get_arg(0)] = func_get_arg(1);
				break;
		}
	}

	public function __call($fn, $arguments)
	{
		if( in_array($fn, $this->allowMethods) ) {
			$this->method = $fn;
			$this->data = $arguments[0]; 
		}

		return $this;
	}

	public function dispatch()
	{
		$ch = curl_init();
		if(!empty($this->data)) {
			$build_query = http_build_query($this->data);
			if($this->method == 'POST') {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $build_query);
			} else {
				$this->url .= ( ( strpos($this->url, '?') !== false ) ? '&' : '?' ) . $build_query;
			}
		}

		if(!empty($this->header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
		}

		//curl_setopt($ch, CURLOPT_USERPWD, 'user::pasword');
		
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$this->body = curl_exec($ch);

		//echo ' --> status [' . $this->url . '] [ ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ']'; 

		curl_close($ch);
		//$this->__clear();
		return $this->body;
	}

	private function __clear(){
		$this->url = NULL;
		$this->method = 'GET';
		$this->data = array();
	}
}

/*
if(isset($options['url']))
				self::$url = $options['url'];	

			self::$method = (isset($options['method'])) ? $options['method'] : 'GET';

			$ch = curl_init();


			if(isset($options['params'])) {
				if(is_array($options['params'])) 
						$options['params'] = http_build_query($options['params']);

				if(self::$method == 'POST')	
					curl_setopt($ch,CURLOPT_POSTFIELDS, $options['params']);
				else if(self::$method == 'GET')
					self::$url .= ((strpos(self::$url, '?') !== false) ? '&' : '?') . $options['params'];  
			}
				
			if(self::$debug)
				echo self::$url;
			
			curl_setopt($ch, CURLOPT_URL, self::$url);
			//@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			//curl_setopt($ch, CURLOPT_ENCODING, 'ISO-8859-1'); 
			curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookieFileLocation);
         	curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookieFileLocation);
			$response = curl_exec($ch);
			
			if(self::$debug)
				echo ' --> status[ ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ']'; 
			
			curl_close($ch);

			self::clearUrl();
			return $response;
 */