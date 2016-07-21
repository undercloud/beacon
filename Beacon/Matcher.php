<?php
namespace Beacon;

use Beacon\Route;

class Matcher
{
	public function checkPath(Route $route, $uri)
	{
		$path = $route->getPath();
		$pattern = '~^' . $path . '(/|$)~';
		
		return preg_match($pattern, $uri);
	}

	public function checkMethod(Route $route, $method)
	{
		$list = $route->getMethod();
	
		return in_array($method, $list);
	}

	public function checkDomain(Route $route, $domain)
	{
		$regexp = $route->getDomain();
	
		if (!$regexp) return true;
	
		return preg_match('~^' . $regexp . '~', $domain);
	}
	
	public function checkSecure(Route $route, $currentSecure)
	{
		$secure = $route->getSecure();
	
		if (true === $secure) {
			if (false === $currentSecure) {
				return false;
			}
		}
		
		return true;
	}
	
	public function checkController(Route $route, $uri)
	{
		if (!$route->getController()) return true;
	
		$controller = $route->getCallback();
		
		$path = $route->getPath();
		$action = Helper::retrieveAction($controller, $path, $uri);
		
		if (!$action) return false;

		$route->setCallback($controller . '::' . $action);
		
		return true;
	}
	
	public function checkWhere(Route $route)
	{
		//$where = $route->getWhere();
	}
}
?>