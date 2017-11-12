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
     * @var array
     */
    private $options = [];

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
                sprintf(
                    'Property %s is not defined in %s',
                    $property,
                    __CLASS__
                )
            );
        }

        if ('set' === $prefix) {
            return $this->{$property} = reset($args);
        } elseif ('get' === $prefix) {
            return $this->{$property};
        }

        throw new Exception(
            sprintf('Method %s is not defined in %s', $method, __CLASS__)
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
        $keys = ['auth','secure','method','middleware','where'];

        foreach ($options as $key => $value) {
            if (in_array($key, $keys, true)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Save temporary options
     *
     * @param array $options list
     *
     * @return null
     */
    public function holdOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Build options;
     *
     * @return null
     */
    public function assignOptions()
    {
        $options = Helper::processOptions($this->options);
        $this->setOptions($options);
    }
}
