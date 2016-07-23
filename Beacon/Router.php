<?php
namespace Beacon;

use Closure;
use Beacon\Route;
use Beacon\Helper;
use Beacon\Matcher;
use Beacon\XmlParser;
use Beacon\RouteException;

class Router
{
	private $host;
	private $method;
	private $secure = false;

	private $domain;
	private $groups = [];
	private $options = [];
	private $controller = false;

	private $routes = [];
	private $lastRoute;
	private $fallbackRoute;

	private $helper;
	private $matcher;

	public function __construct(array $options = [])
	{
		if (isset($options['host']))   $this->host   = $options['host'];
		if (isset($options['secure'])) $this->secure = $options['secure'];
		if (isset($options['method'])) $this->method = strtolower($options['method']);

		$this->matcher = new Matcher;
		$this->helper  = new Helper;

		$this->fallbackRoute = new Route;
		$this->fallbackRoute->setMethod([]);
		$this->fallbackRoute->setCall($this->helper->noop());
	}

	private function processOptions(array $options)
	{
		$this->options[] = $options;
		$options = $this->helper->processOptions($this->options);
		array_pop($this->options);

		return $options;
	}

	public function globals(array $options)
	{
		$this->options = [$options];

		return $this;
	}

	private function bind($path, $call, array $options = [])
	{
		$path = $this->helper->normalize($path);

		$options = $this->processOptions($options);

		$path   = implode($this->groups) . $path;
		$params = $this->helper->extractPlaceholder($path);
		$path   = $this->helper->compile($path);

		$route = new Route;

		$prefix = null;
		if ($this->domain) {
			$route->setDomain($this->domain);
			$prefix = ('<' . $this->domain . '>/');
		}

		$key = null;
		if ($path) {
			$path = $this->helper->normalize($path);
		    $key = $prefix . $path;
		}

		$route->setPath($path);
		$route->setCallback($call);
		$route->setParams($params);
		$route->setOptions($options);

		if ($this->controller) {
			$route->setController($this->controller);
		}

		if (isset($this->routes[$key])){
			throw new RouteException(
				sprintf('Path %s already exists', $key)
			);
		}

		$this->routes[$key] = $this->lastRoute = $route;
	}

	public function on($path, $call, array $options = [])
	{
		$this->bind($path, $call, $options);

		return $this;
	}

	public function match(array $method, $path, $call, array $options = [])
	{
		$options['method'] = $method;
		$this->bind($path, $call, $options);

		return $this;
	}

	public function get($path, $call, array $options = [])
	{
		$options['method'] = ['get'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function post($path, $call, array $options = [])
	{
		$options['method'] = ['post'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function put($path, $call, array $options = [])
	{
		$options['method'] = ['put'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function delete($path, $call, array $options = [])
	{
		$options['method'] = ['delete'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function options($path, $call, array $options = [])
	{
		$options['method'] = ['options'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function head($path, $call, array $options = [])
	{
		$options['method'] = ['head'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function connect($path, $call, array $options = [])
	{
		$options['method'] = ['connect'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function patch($path, $call, array $options = [])
	{
		$options['method'] = ['patch'];
		$this->bind($path, $call, $options);

		return $this;
	}

	public function otherwise($call, array $options = [])
	{
		$options = $this->processOptions($options);
		$this->fallbackRoute->setOptions($options);
		$this->fallbackRoute->setCallback($call);

		return $this;
	}

	public function group($prefix, $call, array $options = [])
	{
		$this->groups[] = $prefix;
		$this->options[] = $options;

		call_user_func($call, $this);

		array_pop($this->options);
		array_pop($this->groups);

		return $this;
	}

	public function domain($domain, $call, array $options = [])
	{
		$this->domain = $domain;
		$this->options[] = $options;

		call_user_func($call, $this);

		array_pop($this->options);
		$this->domain = null;

		return $this;
	}

	public function controller($path, $controller, array $options = [])
	{
		$this->controller = true;
		$this->bind($path, $controller, $options);
		$this->controller = false;

		return $this;
	}

	public function where($param, $regexp, $default = null)
	{
		call_user_func_array([$this->lastRoute, 'where'], func_get_args());

		return $this;
	}

	public function loadFromXml($path)
	{
		(new XmlParser($this))->parse($path);

		return $this;
	}

	public function go($uri)
	{
		krsort($this->routes);

		$uri = $this->helper->normalize($uri);
		foreach ($this->routes as $route) {
			if(!$this->matcher->checkPath($route, $uri)) {
				continue;
			}

			if (!$this->matcher->checkDomain($route, $this->host)) {
				continue;
			}

			if (!$this->matcher->checkMethod($route, $this->method)) {
				RouteError::setErrorCode(RouteError::HTTP_METHOD_ERROR);

				return $this->fallbackRoute;
			}

			if (!$this->matcher->checkSecure($route, $this->secure)) {
				RouteError::setErrorCode(RouteError::SECURE_ERROR);

				return $this->fallbackRoute;
			}

			if (!$this->matcher->checkController($route, $uri)) {
				RouteError::setErrorCode(RouteError::CONTROLLER_RESOLVE_ERROR);

				return $this->fallbackRoute;
			}

			$this->helper->fetchPlaceholder($params, $uri);

			if (!$this->matcher->checkWhere($route)) {
				RouteError::setErrorCode(RouteError::WHERE_REGEX_ERROR);

				return $this->fallbackRoute;
			}

			return $route;
		}

		RouteError::setErrorCode(RouteError::NOT_FOUND_ERROR);

		return $this->fallbackRoute;
	}
}
?>