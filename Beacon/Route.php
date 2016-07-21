<?php
namespace Beacon;

use Exception;

class Route
{
	private $path;
	private $domain;
	private $callback;
	private $secure = false;
	private $params = array();
	private $method = array();
	private $where = array();
	private $controller = false;
	private $middleware = array();
	
	public function __call($method, $args)
	{
		$property = strtolower(substr($method, 3));
		$prefix   = substr($method, 0, 3);

		if ('set' === $prefix) {
			return $this->{$property} = reset($args);
		} else if('get' === $prefix) {
			return $this->{$property};
		}
		
		throw new Exception(
			sprintf('Method %s is not defined in %s', $method,  __CLASS__)
		);
	}
	
	public function where($param, $regexp, $modifier = null)
	{
		$this->where[$param] = '~' . $regexp . '~' . $modifier;
	}
}
?>