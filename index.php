<?php

require __DIR__ . '/src/Beacon/Beacon.php';

$router = new Beacon\Router(array(
	'domain' => $_SERVER['SERVER_NAME'],
	'method' => $_SERVER['REQUEST_METHOD']
));


/*$router
	->on('/api/:one(/:two)', 'Controller::action')
		->where('one', '/\d+/',675)
		->where('two','/\d+/', 'kajar')
	->on('/some', 'Xontroller::uaction');
*/

$router->loadFromXml(__DIR__ . '/beacon.xml');

$call = $router->go(substr($_SERVER['REQUEST_URI'],7));

//var_dump($router);
//$call = $call->getCallback();
//$call();
?>