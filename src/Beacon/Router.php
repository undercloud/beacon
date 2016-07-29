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
	private $isSecured = false;

	private $domain;
	private $groups = [];
	private $options = [];
	private $rest = false;
	private $controller = false;

	private $routes = [];
	private $lastRoute;
	private $fallbackRoute;

	private $helper;
	private $matcher;

	public function __construct(array $options = [])
	{
		if (isset($options['host']))      $this->host   = $options['host'];
		if (isset($options['isSecured'])) $this->isSecured = $options['isSecured'];
		if (isset($options['method']))    $this->method = strtolower($options['method']);

		$this->matcher = new Matcher;
		$this->helper  = new Helper;

		$this->fallbackRoute = new Route;
		$this->fallbackRoute->setCallback($this->helper->noop());
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
		$options = $this->processOptions($options);

		if (!isset($options['method']) or !in_array($this->method, $options['method'])) {
			return;
		}

		$path = $this->helper->normalize($path);
		$path = implode($this->groups) . $path;

		$route = new Route;

		$route->setOrigin($path);

		$path = $this->helper->compile($path);

		$prefix = null;
		if ($this->domain) {
			$route->setDomain($this->domain);
			$prefix = ('<' . $this->domain . '>/');
		}

		$key = null;
		if ($path) {
		    $key = $prefix . $path;
		}

		$route->setPath($path);
		$route->setCallback($call);
		$route->setOptions($options);

		if ($this->controller) {
			$route->setController($this->controller);
		}

		if ($this->rest) {
			$route->setRest($this->rest);
		}

		if (isset($this->routes[$key])) {
			throw new RouteException(
				sprintf('Path %s already exists', $key)
			);
		}

		$this->routes[$key] = $this->lastRoute = $route;
	}

	public function on($path, $call, array $options = [])
	{
		if (!isset($options['method'])) {
			$options['method'] = ['post','get','put','delete'];
		}

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
		$this->on($path, $controller, $options);
		$this->controller = false;

		return $this;
	}

	public function resource($path, $controller, array $options = [])
	{
		$name = 'id';
		if (isset($options['name'])) {
			$name = $options['name'];
			unset($options['name']);
		}

		$this->rest = true;
		$this->get($path, $controller . '::index', $options);
		$this->get($path . '/create', $controller . '::create', $options);
		$this->post($path, $controller . '::store', $options);
		$this->get($path . '/:' . $name, $controller . '::show', $options);
		$this->get($path . '/:' . $name . '/edit', $controller . '::edit', $options);
		$this->put($path . '/:' . $name, $controller . '::update',  $options);
		$this->delete($path . '/:' . $name, $controller . '::destroy', $options);
		$this->rest = false;

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
		if (false !== ($pos = strpos($uri, '?'))) {
			$uri = substr($uri, 0, $pos);
		}

		$uri = rawurldecode($uri);
		$uri = $this->helper->normalize($uri);

		krsort($this->routes);

		foreach ($this->routes as $route) {
			if (!$this->matcher->checkPath($route, $uri)) {
				continue;
			}

			if (!$this->matcher->checkDomain($route, $this->host)) {
				continue;
			}

			if (!$this->matcher->checkSecure($route, $this->isSecured)) {
				RouteError::setErrorCode(RouteError::SECURE_ERROR);

				return $this->fallbackRoute;
			}

			if (!$this->matcher->checkController($route, $uri)) {
				RouteError::setErrorCode(RouteError::CONTROLLER_RESOLVE_ERROR);

				return $this->fallbackRoute;
			}

			if (!$this->matcher->checkRest($route)) {
				RouteError::setErrorCode(RouteError::REST_RESOLVE_ERROR);

				return $this->fallbackRoute;
			}

			$this->helper->fetchPlaceholder($route, $uri);

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