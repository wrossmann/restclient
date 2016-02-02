<?php
/**
 * @package	wrossmann/restclient
 * @author	Wade Rossmann <wrossmann@gmail.com>
 */
 
namespace wrossmann\restclient;

/**
 * Generic Resource class
 * 
 * The purpose of this class is to allow the representation of a restclient URI
 * as a chain of object properties, usually ending in a function call to
 * perform the request.
 * 
 * eg:
 * 
 *     $client->dns->managed->$domain_id->record()
 * 
 * is equivalent to:
 *     
 *     GET /dns/managed/123456/records
 */
class Resource implements ResourceInterface {
	/**
	 * @var	object	$parent		Handle to the parent \DNSME\Client or \DNSME\Resource object
	 * @var string	$name		Resource name
	 * @var array	$_resources	Cache for pre-instantiated Resource objects
	 */
	protected $parent;
	protected $name;
	protected $_resources = [];
	
	/**
	 * Constructor
	 * @param	object	$parent		Handle to the parent Client or Resource object
	 * @param	string	$name		Resource name
	 */
	public function __construct(ResourceInterface $parent, $name) {
		$this->parent = $parent;
		$this->name = $name;
	}
	
	/**
	 * Magic Call Handler
	 * 
	 * Magic function calls must conform to the spec:
	 * 
	 *     Resource::function_name(array $api_params, string $method='get')
	 * 
	 * @param	string	$name	Name of the function called
	 * @param	array	$args	Arguments the the called function
	 * @return	object			Guzzle Repsonse object
	 */
	public function __call($name, $args) {
		$params = isset($args[0]) ? $args[0] : [];
		if( ! is_array($params) ) {
			throw new \InvalidArgumentException(
				sprintf('Argument 1 passed to %s::%s must be an instance of %s, %s given',
					__CLASS__, __FUNCTION__, 'array', gettype($params))
			);
		}
		
		$method = isset($args[1]) ? $args[1] : 'get';
		$acceptable = ['get', 'post', 'put', 'delete'];	// ONE MILLION YEARS DUNGEON
		if( ! in_array($method, $acceptable) ) {
			throw new \InvalidArgumentException(
				sprintf('Argument 2 passed to %s::%s must be one of %s, %s given',
					__CLASS__, __FUNCTION__, implode(',', $acceptable), $method)
			);
		}
		
		return $this->execute($name, $params, $method);
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
	 * Pass the execution request to the parent object, return value is
	 * passed back through to the caller.
	 * 
	 * @param	string	$uri	The URI of the request
	 * @param	array	$params	The parameters of the request
	 * @param	string	$method	The HTTP method to use
	 * @return	object			Guzzle Response object
	 */
	public function execute($uri, $params=[], $method='get') {
		if( $uri === '' ) {
			$uri = $this->name;
		} else {
			$uri = sprintf('%s/%s', $this->name, $uri);
		}
		return $this->parent->execute($uri, $params, $method);
	}
}
