<?php
namespace Beacon;

use Closure;

/**
 * ClosureQueue
 *
 * @category Router
 * @package  Beacon
 * @author   undercloud <lodashes@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     http://github.com/undercloud/beacon
 */
class ClosureQueue
{
    /**
     * @var array
     */
    protected $queue = [];

    /**
     * @var Route
     */
    protected $route;

    /**
     * Initialize
     *
     * @param Router $route instance
     */
    public function __construct(Router $route)
    {
        $this->route = $route;
    }

    /**
     * Magic __invoke
     *
     * @return mixeds
     */
    public function __invoke()
    {
        return call_user_func($this->getClosure());
    }

    /**
     * Wrap callback
     *
     * @param callable $callback  value
     * @param array    $arguments list
     *
     * @return Closure
     */
    public function wrap($callback, array $arguments = [])
    {
        $callback = array($this->route, $callback);

        return function () use ($callback, $arguments) {
            return call_user_func_array($callback, $arguments);
        };
    }

    /**
     * Enqueue closure
     *
     * @param Closure $closure instance
     *
     * @return self
     */
    public function enqueue(Closure $closure)
    {
        $this->queue[] = $closure;

        return $this;
    }

    /**
     * Get callback
     *
     * @return Closure
     */
    public function getClosure()
    {
        $queue = $this->queue;

        return function () use ($queue) {
            foreach ($queue as $q) {
                call_user_func($q);
            }
        };
    }
}