<?php
namespace Beacon\Tests;

use Beacon\Router;
use PHPUnit_Framework_TestCase;

class BeaconTest extends PHPUnit_Framework_TestCase
{
    private $router;

    public function setUp()
    {
        error_reporting(-1);
        $this->router = require(__DIR__ . '/BeaconPreset.php');
    }

    public function testBind()
    {
        $route = $this->router
            ->on('/api/users', 'Beacon\Tests\ControllerApi::getUsers')
            ->go('/api/users/');

        $this->assertEquals(
            'Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
    }

    public function testMethod()
    {
        $route = $this->router
            ->get('/api/users', 'Beacon\Tests\ControllerApi::getUsers')
            ->go('/api/users/');

        $this->assertEquals(
            'Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
    }

    public function testMatch()
    {
        $route = $this->router
            ->match(['get', 'post'], '/api/users', 'Beacon\Tests\ControllerApi::getUsers')
            ->go('/api/users/');

        $this->assertEquals(
            'Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
    }

    public function testSecure()
    {
        $route = $this->router
            ->on('/api/users', 'Beacon\Tests\ControllerApi::getUsers')
                ->withSecure(true)
            ->go('/api/users/');

        $this->assertEquals(
            'Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
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

        $this->assertEquals(
            '\Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
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
                ->withWhere('id', '/\d+/' , 1)
                ->withWhere('nickname', '/[A-Za-z]+/' , 'Guest')
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

        $this->assertEquals(
            '\Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
    }

    public function testDomain()
    {
        $route = $this->router
            ->domain('example.com', function($router) {
                $router->on('/users', '\Beacon\Tests\ControllerApi::getUsers');
            })
            ->go('/users');

        $this->assertEquals(
            '\Beacon\Tests\ControllerApi::getUsers',
            $route->getCallback()
        );
    }

    public function testMiddleware()
    {
        $this
            ->router
            ->globals()
                ->withMiddleWare(['A'])
            ->on('/', null)
                ->withMiddleware(['B','C'])
            ->group('/api', function($router) {
                $router
                    ->on('/user', null)
                        ->withoutMiddleware('A')
                    ->on('/article', null)
                        ->withoutAnyMiddleware()
                        ->withMiddleware('D');
            })
                ->withMiddleware('E')
            ->otherwise(null)
                ->withMiddleware('F');

        $this->assertEquals(
            ['A','B','C'],
            $this->router->go('/')->getMiddleware()
        );

        $this->assertEquals(
            ['E'],
            $this->router->go('/api/user')->getMiddleware()
        );

        $this->assertEquals(
            ['D'],
            $this->router->go('/api/article')->getMiddleware()
        );

        $this->assertEquals(
            ['A','F'],
            $this->router->go('/404')->getMiddleware()
        );
    }

    public function testWildcard()
    {
        $this
            ->router
            ->on('/foo', null)
                ->wildcard('bat');

        $this->assertEquals(
            ['bat' => ['bar','baz','quux']],
            $this->router->go('/foo/bar/baz/quux')->getParams()
        );

        $this->assertEquals(
            ['bat' => ['bar','baz']],
            $this->router->go('/foo/bar/baz/')->getParams()
        );

        $this->assertEquals(
            ['bat' => ['bar']],
            $this->router->go('/foo/bar')->getParams()
        );

        $this->assertEquals(
            ['bat' => []],
            $this->router->go('/foo/')->getParams()
        );
    }

    public static $auth = false;

    public function auth()
    {
        return self::$auth;
    }

    public static $subAuth = false;

    public function subAuth()
    {
        return self::$subAuth;
    }

    public function testAuth()
    {
        $thisis = $this;

        $this
            ->router
            ->on('/dashboard', function(){
                return true;
            })
                ->withAuth('Beacon\\Tests\\BeaconTest::auth')
            ->otherwise(function()use($thisis){
                $error = \Beacon\RouteError::getErrorCode();
                $thisis->assertEquals($error,\Beacon\RouteError::AUTH_ERROR);

                return false;
            });

        $route = $this->router->go('/dashboard');
        $this->assertFalse(call_user_func($route->getCallback()));

        self::$auth = true;
        $route = $this->router->go('/dashboard');
        $this->assertTrue(call_user_func($route->getCallback()));

        $this
            ->router
            ->group('/api', function($route){
                $route->on('/user', function(){
                    return true;
                })->withAuth('Beacon\\Tests\\BeaconTest::subAuth');
            })->withAuth('Beacon\\Tests\\BeaconTest::auth')
            ->otherwise(function(){
                return false;
            });

        self::$auth = false;
        $route = $this->router->go('/api/user');
        $this->assertFalse(call_user_func($route->getCallback()));

        self::$auth = true;
        $route = $this->router->go('/api/user');
        $this->assertFalse(call_user_func($route->getCallback()));
/*
        self::$subAuth = true;
        $route = $this->router->go('/api/user');
        $this->assertTrue(call_user_func($route->getCallback()));
        */
    }
}
