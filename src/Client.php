<?php

namespace wrossmann\restclient;

class Client implements ResourceInterface {
	protected $_client;
	protected $_resources = [];
	protected $_loggers = [];
	protected $_root;
	
	/**
	 * Constructor
	 * @param	object	$client	Guzzle Client object
	 */
	public function __construct(\GuzzleHttp\Client $client) {
		$this->_client = $client;
		$this->_root = new Resource($this, '');
	}
	
	/**
	 * Return a Resource object for the specified name
	 * @param	string	$name	Resource name
	 * @return	Resource
	 */
	public function __get($name) {
		if( ! isset($this->_resources[$name]) ) {
			$this->_resources[$name] = new Resource($this, $name);
		}
		return $this->_resources[$name];
	}
	
	/**
	 * Cover the case of a first-level resource being called
	 * 
	 * eg: $restclient->foo() == base-url/foo
	 * 
	 * This also covers calling the root resource via $restclient->{''}()
	 * @param	string	$name	Name of the function called
	 * @param	array	$args	Arguments the the called function
	 * @return	object			Guzzle Repsonse object
	 */
	public function __call($name, $args) {
		return $this->$name->__call('', $args);
	}

	// HTTP Methods //	
	/**
	 * Perform a GET request against the API
	 * @param	string	$path	URI path for request
	 * @param	array	$in_params	Parameters for API request
	 */
	public function get($path, $in_params=[]) {
		$params = [ 'headers' => $this->basicHeaders(), 'query' => $in_params ];
		return $this->guzzle('get', $path, $params);
	}
	
	/**
	 * Perform a POST request against the API
	 * @param	string	$path	URI path for request
	 * @param	array	$in_params	Parameters for API request
	 */
	public function post($path, $in_params=[]) {
		$params = [ 'headers' => $this->basicHeaders(), 'body' => $this->encodeParameters($in_params) ];
		return $this->guzzle('post', $path, $params);
	}

	/**
	 * Perform a DELETE request against the API
	 * @param	string	$path	URI path for request
	 * @param	array	$in_params	Parameters for API request
	 */
	public function delete($path, $in_params=[]) {
		$params = [ 'headers' => $this->basicHeaders() ];
		if( !empty($in_params) ) { $params['body'] = $this->encodeParameters($in_params); }
		return $this->guzzle('delete', $path, $params);
	}

	/**
	 * Perform a PUT request against the API
	 * @param	string	$path	URI path for request
	 * @param	array	$in_params	Parameters for API request
	 * @todo	Implement!
	 */
	public function put($path, $in_params=[]) {
		$params = [ 'headers' => $this->basicHeaders(), 'body' => $this->encodeParameters($in_params) ];
		return $this->guzzle('put', $path, $params);
	}

	/**
	 * Execute a request against the API
	 * @param	string	$uri	The URI of the request
	 * @param	array	$params	The parameters of the request
	 * @param	string	$method	The HTTP method to use
	 * @return	object			Guzzle Response object
	 */	
	public function execute($uri, $params=[], $method='get') {
		$this->log(
			'%s::%s(%s, %s, %s)',
			[__CLASS__, __FUNCTION__, $uri, json_encode($params), $method], 'debug');
		
		return $this->$method($uri, $params);
	}
	
	/**
	 * Attach a PSR-3 compatible logger
	 * @param	object	$logger	A PSR-3 compatible logger object.
	 */
	public function attachLogger(\Psr\Log\LoggerInterface $logger) {
		$this->_loggers[] = $logger;
	}
	
	/**
	 * Encode request parameters. Default to JSON, override method for others.
	 * @param array $params
	 * @return string
	 */
	protected function encodeParameters($params) {
		return json_encode($params); 
	}
	
	/**
	 * Get basic headers for the request
	 * 
	 * Intended to be re-declared in an extending class to return headers
	 * used in *all* requested, such as Content-Type, Accept, authentication
	 * headers, etc.
	 * 
	 * @return array
	 */
	protected function basicHeaders() {
		return [];
	}
	
	/**
	 * Quick and dirty passthrough to the logger.
	 * @param	string	$msg	The message to be logged. Can be in sprintf() format with flags passed in in $args
	 * @param	array	$args	Optional arguments for sprintf()
	 * @param	string	$level	PSR-3 logging level. [default: degug]
	 */
	protected function log($msg, $args=NULL, $level='info') {
		if( empty($this->_loggers) ) { return; }
		if( $msg[strlen($msg)-1] != "\n" ) { $msg .= "\n"; }
		if( is_null($args) ) { $args = [$msg]; }
		else { array_unshift($args, $msg); }
		foreach($this->_loggers as $logger) {
			$logger->{'add'.$level}(call_user_func_array('sprintf', $args));
		}
	}
	
	/**
	 * Generic method to perform a Guzzle request
	 * @param	string	$method		HTTP method
	 * @param	string	$uri		URI to request
	 * @param	array	$params		Request parameters
	 */
	protected function guzzle($method, $uri, $params) {
		$this->log('Executing %s on URI [Base: "%s", Uri: "%s"] with params %s',
			[strtoupper($method), $this->_client->getBaseUrl(), $uri, json_encode($params)], 'debug');
		$response = $this->_client->$method($uri, $params);
		$this->log('API Response Status: %s %s', [$response->getStatusCode(), $response->getReasonPhrase()], 'debug');
		return $response;
	}
}
