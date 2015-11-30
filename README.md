# REST Client Wrapper

Provides an implementation-agnostic client and abstracts away the details of interacting with a REST API, translates requests like:

* `GET http://example.com/resource/{id}/action` to `$rest->resource->$id->action()`
* `GET http://example.com/resource/{id}/action?param=foo` to `$rest->resource->$id->action(['param'=>'foo'])`
* `POST http://example.com/resource` with data `param=foo` to `$rest->resource->$id->action(['param'=>'foo'], 'post')`
* and so on for `DELETE and PUT` as well.

Resource instantiation is chained and cached for speed and memory efficiency, and requests are executed by the single Guzzle instance in the root `REST\Client` object.

## Authentication

Easily supports REST endpoints that rely on authentication tokens in the headers via the `basicHeaders()` function which can be modified to set token values, as well as common required `Accept:` and `Content-Type` headers, eg:

	public function basicHeaders() {
		return [
			'Accept'	=> 'application/json',
			'Content-Type'	=> 'application/json',
			'X-Auth-Token'	=> isset($this->authToken) ? $this->authToken : '';
		];
	}

**Note:** Currently the client does not have a baked-in way to deal with REST services that require request signing, but you should be able to override `Client::guzzle()` to accomplish this.
