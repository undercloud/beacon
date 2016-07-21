<?php
require __DIR__ . '/Beacon/Route.php';
require __DIR__ . '/Beacon/Router.php';
require __DIR__ . '/Beacon/Helper.php';
require __DIR__ . '/Beacon/Matcher.php';
require __DIR__ . '/Beacon/RouteException.php';
require __DIR__ . '/Beacon/RouteError.php';

$router = new Beacon\Router(array(
	'domain' => $_SERVER['SERVER_NAME'],
	'method' => $_SERVER['REQUEST_METHOD']
));

$router
	->group('/api', function($router){
		$router
			->on('/a', function(){
				echo 'lalka';
			},
			array(
				'middleware' => array('add:My','del:Two'),
				'method' => array('get')
			)
		);
	},
	array('middleware' => array('One','Two')))
	->on('/api', function(){
		echo 'me';
	})
	->otherwise(function(){
		echo '404';
	});
	
$call = $router->go(substr($_SERVER['REDIRECT_URL'],7));

var_dump($call);
//$call = $call->getCallback();
//$call();
?>