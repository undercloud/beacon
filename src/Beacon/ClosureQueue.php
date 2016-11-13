<?php
namespace Beacon;

use Closure;

class ClosureQueue
{
    protected $queue = array();
    protected $route;

    public function __construct(Router $route)
    {
        $this->route = $route;
    }

    public function __invoke()
    {
        return call_user_func($this->getClosure());
    }

    public function wrap($callback, array $arguments = array())
    {
        $callback = array($this->route, $callback);

        return function () use ($callback, $arguments) {
            return call_user_func_array($callback, $arguments);
        };
    }

    public function enqueue(Closure $closure)
    {
        $this->queue[] = $closure;

        return $this;
    }

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