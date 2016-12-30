<?php
namespace Beacon;

use Exception;

/**
 * Route
 *
 * @category Router
 * @package  Beacon
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/beacon
 */
class Route
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $method = [];

    /**
     * @var array
     */
    private $where = [];

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var array
     */
    private $middleware = [];

    /**
     * @var boolean
     */
    private $rest = false;

    /**
     * @var boolean
     */
    private $secure = false;

    /**
     * @var boolean
     */
    private $controller = false;

    /**
     * @var string
     */
    private $wildcard;

    /**
     * @var callable
     */
    private $auth;

    /**
     * Magic __call
     *
     * @param string $method name
     * @param array  $args   list
     *
     * @return mixed
     */
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

    /**
     * Set options
     *
     * @param array $options list
     *
     * @return null
     */
    public function setOptions(array $options)
    {
        $keys = ['secure','method','middleware','where'];

        foreach ($options as $key => $value) {
            if (in_array($key, $keys)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Process where
     *
     * @param string $param   name
     * @param string $regexp  expression
     * @param string $default value
     *
     * @return null
     */
    public function where($param, $regexp, $default = null)
    {
        $where = ['regexp' => $regexp];

        if (isset(func_get_args()[2])) {
            $where['default'] = $default;
        }

        $this->where[$param] = $where;
    }
}
