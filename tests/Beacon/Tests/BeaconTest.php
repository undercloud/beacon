<?php
namespace Beacon\Tests;

use Beacon\Router;
use PHPUnit_Framework_TestCase;

class BeaconTest extends PHPUnit_Framework_TestCase
{
	private $router;

	public function __construct()
	{
		error_reporting(-1);
		$this->router = require(__DIR__ . '/BeaconPreset.php');
	}

	public function testBind()
	{
		$route = $this->router
			->on('/api/users', 'Beacon\Tests\ControllerApi::getUsers')
			->go('/api/users/');

		$this->assertEquals('Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testMethod()
	{
		$route = $this->router
			->get('/api/users', 'Beacon\Tests\ControllerApi::getUsers')
			->go('/api/users/');

		$this->assertEquals('Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testMatch()
	{
		$route = $this->router
			->match(['get', 'post'], '/api/users', 'Beacon\Tests\ControllerApi::getUsers')
			->go('/api/users/');

		$this->assertEquals('Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testSecure()
	{
		$route = $this->router
			->on('/api/users', 'Beacon\Tests\ControllerApi::getUsers', ['secure' => true])
			->go('/api/users/');

		$this->assertEquals('Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testOtherwise()
	{
		$route = $this->router
			->otherwise(function(){
				return 404;
			})
			->go('/');

		$this->assertEquals(404, call_user_func($route->getCallback()));
	}

	public function testController()
	{
		require __DIR__ . '/ControllerApi.php';

		$route = $this->router
			->controller('/api', '\Beacon\Tests\ControllerApi')
			->go('/api/get-users/');

		$this->assertEquals('\Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testParams()
	{
		$route = $this->router
			->on('/api/:username/auth/:apikey(/:optional)', null)
			->go('/api/john/auth/secret');

		$params = $route->getParams();

		$this->assertEquals('john', $params['username']);
		$this->assertEquals('secret', $params['apikey']);
		$this->assertEquals(null, $params['optional']);
	}

	public function testWhere()
	{
		$route = $this->router
			->on('/user/:id(/:nickname)', null)
				->where('id', '/\d+/' , 1)
				->where('nickname', '/[A-Za-z]+/' , 'Guest')
			->go('/user/num');

		$params = $route->getParams();

		$this->assertEquals(1, $params['id']);
		$this->assertEquals('Guest', $params['nickname']);
	}

	public function testGroup()
	{
		$route = $this->router
			->group('/api', function($router) {
				$router->on('/users', '\Beacon\Tests\ControllerApi::getUsers');
			})
			->go('/api/users');

		$this->assertEquals('\Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testDomain()
	{
		$route = $this->router
			->domain('example.com', function($router) {
				$router->on('/users', '\Beacon\Tests\ControllerApi::getUsers');
			})
			->go('/users');

		$this->assertEquals('\Beacon\Tests\ControllerApi::getUsers', $route->getCallback());
	}

	public function testMiddleware()
	{
		$this->router
			->globals([
				'middleware' => ['A']
			])
			->on('/', null, ['middleware' => ['add:B','add:C']])
			->group('/api', function($router) {
				$router
					->on('/user', null, ['middleware' => ['del:A']])
					->on('/article', null, ['middleware' => ['D']]);
			}, ['middleware' => 'add:E'])
			->otherwise(null, ['middleware' => ['add:F']]);

		$this->assertEquals(['A','B','C'], $this->router->go('/')->getMiddleware());
		$this->assertEquals(['E'], $this->router->go('/api/user')->getMiddleware());
		$this->assertEquals(['D'], $this->router->go('/api/article')->getMiddleware());
		$this->assertEquals(['A','F'], $this->router->go('/404')->getMiddleware());
	}
}

?>