<?php
namespace Beacon;

use ReflectionClass;
use ReflectionException;

/**
 * Matcher
 *
 * @category Router
 * @package  Beacon
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/beacon
 */
class Matcher
{
    /**
     * Compare path
     *
     * @param Route  $route instance
     * @param string $uri   path
     *
     * @return bool
     */
	public function checkPath(Route $route, $uri)
	{
		$path = $route->getPath();
		$pattern = '~^' . $path . '(/|$)~';

		return preg_match($pattern, $uri);
	}

    /**
     * Compare domain
     *
     * @param Route  $route instance
     * @param string $host  name
     *
     * @return bool
     */
	public function checkDomain(Route $route, $host)
	{
		$regexp = $route->getDomain();

		if (!$regexp) {
            return true;
        }

		return preg_match('~^' . $regexp . '$~', $host);
	}

    /**
     * Check secure connection
     *
     * @param Route $route         instance
     * @param bool  $currentSecure flag
     *
     * @return bool
     */
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

    /**
     * Check controller
     *
     * @param Route  $route instance
     * @param string $uri   string
     *
     * @return bool
     */
	public function checkController(Route $route, $uri)
	{
		if (!$route->getController()) {
            return true;
        }

		$path = $route->getPath();
		$slice = substr($uri, strlen($path));

		if (!$slice) {
            return false;
        }

		$filtered = array_filter(explode('/', $slice));
		$action = reset($filtered);
		$action = preg_replace('~\W~', '', (string)$action);

		if (!$action) {
            return false;
        }

		$controller = $route->getCallback();

		try {
			$class = new ReflectionClass($controller);
		} catch (ReflectionException $e) {
			return false;
		}

		if (!$class->hasMethod($action)) {
            return false;
        }

		if (!$class->getMethod($action)->isPublic()) {
            return false;
        }

		$route->setCallback($controller . '::' . $class->getMethod($action)->getName());

		return true;
	}

    /**
     * Check REST
     *
     * @param Route $route instance
     *
     * @return bool
     */
	public function checkRest(Route $route)
	{
		if (!$route->getRest()) {
            return true;
        }

		list($controller, $action) = explode('::', $route->getCallback());

		return method_exists($controller, $action);
	}

    /**
     * Check where params
     *
     * @param Route $route instance
     *
     * @return bool
     */
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
    
    /**
     * Check auth
     *
     * @param Route $route instance
     *
     * @return bool
     */
    public function checkAuth(Route $route)
    {
        $auth = $route->getAuth();
        
        if(null === $auth) {
            return true;   
        }
        
        return (bool) call_user_func($auth, $route);
    }
}
