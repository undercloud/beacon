<?php
namespace Beacon;

use ReflectionClass;
use ReflectionException;

class Matcher
{
	public function checkPath(Route $route, $uri)
	{
		$path = $route->getPath();
		$pattern = '~^' . $path . '(/|$)~';

		return preg_match($pattern, $uri);
	}

	public function checkDomain(Route $route, $host)
	{
		$regexp = $route->getDomain();

		if (!$regexp) return true;

		return preg_match('~^' . $regexp . '$~', $host);
	}

	public function checkSecure(Route $route, $currentSecure)
	{
		$secure = $route->getSecure();

		if ($secure) {
			if (!$currentSecure) {
				return false;
			}
		}

		return true;
	}

	public function checkController(Route $route, $uri)
	{
		if (!$route->getController()) return true;

		$path = $route->getPath();
		$slice = substr($uri, strlen($path));

		if (!$slice) return false;

		$filtered = array_filter(explode('/', $slice));
		$action = reset($filtered);
		$action = preg_replace('~\W~', '', (string)$action);

		if (!$action) return false;

		$controller = $route->getCallback();

		try {
			$class = new ReflectionClass($controller);
		} catch (ReflectionException $e) {
			return false;
		}

		if (!$class->hasMethod($action)) return false;
		if (!$class->getMethod($action)->isPublic()) return false;

		$route->setCallback($controller . '::' . $class->getMethod($action)->getName());

		return true;
	}

	public function checkRest(Route $route)
	{
		if (!$route->getRest()) return true;

		list($controller, $action) = explode('::', $route->getCallback());

		return method_exists($controller, $action);
	}

	public function checkWhere(Route $route)
	{
		$where = $route->getWhere();
		$params = $route->getParams();

		foreach ($params as $key => $value) {
			if (isset($where[$key])) {
				if (!preg_match($where[$key]['regexp'], $value)) {
					if (isset($where[$key]['default'])) {
						$params[$key] = $where[$key]['default'];
						$route->setParams($params);
					} else {
						return false;
					}
				}
			}
		}

		return true;
	}
}