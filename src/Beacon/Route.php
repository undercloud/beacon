<?php
namespace Beacon;

use Exception;

class Route
{
	private $path;
	private $origin;
	private $domain;
	private $callback;
	private $method = [];
	private $where = [];
	private $params = [];
	private $middleware = [];
	private $rest = false;
	private $secure = false;
	private $controller = false;
	private $wildcard;

	public function __call($method, $args)
	{
		$prefix   = substr($method, 0, 3);
		$property = strtolower(substr($method, 3));

		if (!property_exists($this, $property)) {
			throw new Exception(
				sprintf('Property %s is not defined in %s', $property,  __CLASS__)
			);
		}

		if ('set' === $prefix) {
			return $this->{$property} = reset($args);
		} else if ('get' === $prefix) {
			return $this->{$property};
		}

		throw new Exception(
			sprintf('Method %s is not defined in %s', $method,  __CLASS__)
		);
	}

	public function setOptions(array $options)
	{
		$keys = ['secure','method','middleware','where'];

		foreach ($options as $key => $value) {
			if (in_array($key, $keys)) {
				$this->{$key} = $value;
			}
		}
	}

	public function where($param, $regexp, $default = null)
	{
		$where = ['regexp' => $regexp];

		if (isset(func_get_args()[2])) {
			$where['default'] = $default;
		}

		$this->where[$param] = $where;
	}
}