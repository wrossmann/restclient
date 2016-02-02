<?php
/**
 * @package	wrossmann/restclient
 * @author	Wade Rossmann <wrossmann@gmail.com>
 */
 
namespace wrossmann\restclient;

/**
 * Resource interface
 */
interface ResourceInterface {
	/**
	 * Execute a request against the API
	 * @param	string	$uri	The URI of the request
	 * @param	array	$params	The parameters of the request
	 * @param	string	$method	The HTTP method to use
	 * @return	object			Guzzle Response object
	 */	
	public function execute($uri, $params=[], $method='get');
	
	/**
	 * Return a Resource object for the specified name
	 * @param	string	$name	Resource name
	 * @return	\DNSME\Resource
	 */
	public function __get($name);
}
