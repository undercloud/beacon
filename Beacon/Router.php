<?php
namespace Beacon;

use Closure;
use Beacon\Route;
use Beacon\Helper;
use Beacon\Matcher;
use Beacon\RouteException;

class Router
{
	private $domain;
	private $method;
	private $groups = array();
	private $routes = array();
	private $secure = false;
	private $options = array();
	private $controller = false;
	private $lastRoute;
	private $helper;
	private $matcher;

	public function __construct(array $options = array())
	{
		if (isset($options['domain'])) $this->domain = $options['domain'];
		if (isset($options['secure'])) $this->secure = $options['secure'];
		if (isset($options['method'])) $this->method = strtolower($options['method']);

		$this->matcher = new Matcher;
		
		$this->bind('',function () {});
	}

	private function bind($path, $call, array $options = array())
	{
		$this->options[] = $options;
		$options = Helper::processOptions($this->options);
		array_pop($this->options);
	
		$path   = implode($this->groups) . $path;
		$params = Helper::extractPlaceholder($path);
		$path   = Helper::compile($path);
		
		$key = null;
		if ($path) {	
			$path = Helper::normalize($path);
		    $key = $prefix . $path;
		}
		
		$route = new Route;

		$route->setPath($path);
		$route->setCallback($call);
		$route->setParams($params);
		
		if (isset($options['secure'])) {
			$route->setSecure($options['secure']);
		}
		
		if (isset($options['method'])) {
			$route->setMethod(Helper::arrayify($options['method']));
		} else {
			$route->setMethod(array('post','get','put','delete','head'));
		}
		
		if (isset($options['middleware'])) {
			$route->setMiddleware($options['middleware']);
		}
		
		$prefix = null;
		if ($this->domain) {
			$route->setDomain($this->domain);
			$prefix = ('<' . $this->domain . '>//');
		}

		if ($this->controller) {
			$route->setController($this->controller);
		}

		if (isset($this->routes[$key])){
			throw new RouteException(
				sprintf('Path %s already exists', $key)
			);
		}

		$this->lastRoute = $route;
		$this->routes[$key] = $route;
	}
	
	public function on($path, $call, array $options = array())
	{
		$this->bind($path, $call, $options);

		return $this;
	}
	
	public function match(array $method, $path, $call, array $options = array())
	{
		$options['method'] = $method;
		$this->bind($path, $call, $options);
	
		return $this;
	}

	public function group($prefix, $call, array $options = array())
	{
		$this->groups[] = $prefix;
		$this->options[] = $options;

		call_user_func($call, $this);
		
		array_pop($this->options); 
		array_pop($this->groups);
		
		return $this;
	}
	
	public function domain($domain, $call, $options = array())
	{
		$this->domain = $domain;
		$this->options[] = $options;
		
		call_user_func($call, $this);
		
		array_pop($this->options);
		$this->domain = null;
		
		return $this;
	}
	
	public function controller($path, $controller)
	{
		$this->controller = true;
		$this->on($path, $controller);
		$this->controller = false;

		return $this;
	}

	public function otherwise($call)
	{
		$this->routes['']->setCallback($call);

		return $this;
	}
	
	public function where($param, $regexp, $modifier = null)
	{
		$this->lastRoute->where($param, $regexp, $modifier);
	
		return $this;
	}
	
	public function go($uri)
	{
		krsort($this->routes);
	
		$uri = Helper::normalize($uri);
		foreach ($this->routes as $route) {
			if($this->matcher->checkPath($route, $uri)) {
				if (!$this->matcher->checkMethod($route, $this->method)) {
					die('method');
				}
			
				if (!$this->matcher->checkDomain($route, $this->domain)) {
					die('domain');
				}

				if (!$this->matcher->checkSecure($route, $this->secure)) {
					die('secure');
				}
				
				if (!$this->matcher->checkController($route, $uri)) {
					die('controller');
				}

				$params = $route->getParams();
				$params = Helper::fetchPlaceholder($params, $uri);
				$route->setParams($params);
			
				return $route;
			}
		}
	}
}

?>