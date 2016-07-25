#Beacon - PHP Routing System
[![Build Status](https://travis-ci.org/undercloud/beacon.svg?branch=master)](https://travis-ci.org/undercloud/beacon)
##Features
- Zero dependency
- PCRE pattern path support
- Route groups
- Domain condition support
- HTTPS condition support
- Controller bindings
- RESTfull

##Requirements
PHP 5.4+
##Install
by composer  
```JSON
{
    "require": {
        "undercloud/beacon": "*"
    }
}
```
##Setup
```PHP
// if installed by composer
require '/path/to/vendor/autoload.php';
// if installed manually
require '/path/to/Beacon/Beacon.php';

$router = new Beacon\Router(
  // current hostname
  'host'   => $_SERVER['SERVER_NAME'],
  // current http method
  'method' => $_SERVER['REQUEST_METHOD'],
  // optionaly, true if request over https
  'isSecured' => true
);
```
##Define routes
Simple example:
```PHP
$router
  ->on('/', 'Controller::index')
  ->get('/user/:id', 'ControllerUser::getUser')
  ->post('/user/:id', function () { ... })
  ->put('/user/:id', function () { ... })
  ->delete('/user/:id(/:nickname)', 'ControllerUser::remove')
  ->otherwise(function() {
    echo 404;
  });

// return Beacon\Route object
$route = $router->go($path);
```
###Methods
Complete list of avail route methods
```PHP
/* $path - request path, example /users 
 * $call - Controller::action or Closure
 * $options - array of options
 * $methods - array of http methods, example ['post','put']
 */

$router->on($path, $call [, $options]);
$router->get($path, $call [, $options]);
$router->post($path, $call [, $options]);
$router->put($path, $call [, $options]);
$router->delete($path, $call [, $options]);
$router->match(array $methods, $path, $call [, $options]);
```
###Params
Beacon supports named params.
For example route with binded params:
```PHP
$router->on('/user/:id/:name(/:nickname)', 'ControllerUser::getUser');
```
with request:
```PHP
$route->go('/user/78/John');
```
will be fetched into:
```PHP
[
  'id'       => 78,
  'name'     => 'John',
  'nickname' => null
]
```
You can also add additional check for params:
```PHP
$router
  ->on('/user/:id/:name(/:nickname)', 'ControllerUser::getUser')
    // check numeric id
    ->where('id', '/\d+/')
    // empty or invalid nickname will be replaced with 'Guest'
    ->where('nickname', '/[A-Za-z0-9]+/', 'Guest')
  ->on(...);
```
Now params will be fetched into:
```PHP
[
  'id'       => 78,
  'name'     => 'John',
  'nickname' => 'Guest'
]
```
###Otherwise
If request cannot be resolved, you can define fallback.
```PHP
$router
	->otherwise(function(){
		switch(Beacon\RouterError::getErrorCode()){
			case Beacon\RouterError::NO_ERROR:
			/* All fine, only for example, never exists in otherwise block */
			break;
			
			case Beacon\RouterError::NOT_FOUND_ERROR:
  			/* Same as 404 error, cannot find any
  			path for current request */
  			break;
  	
			case Beacon\RouterError::SECURE_ERROR:
			/* Need secured connection over https */
			break;
	
			case Beacon\RouterError::CONTROLLER_RESOLVE_ERROR:
			/* When given method in binded contoller is unavailable */
			break;
	
			case Beacon\RouterError::WHERE_REGEX_ERROR:
			/* Fail parameter regex test in ->where(...) */
			break;
	
			case Beacon\RouterError::REST_RESOLVE_ERROR:
			/* Cannot find implemented method 
			in given REST controller*/
			break;
      	}
	});
```
###Controller
You can define controller namespace and bind methods to path:
```PHP
$router->controller('/users', 'ControllerUsers');

$route = $route->go('/users/get-users');
// will return ControllerUsers::getUsers
$call = $route->getCallback();
```
if requested method undefined or is not public, Beacon return fallback function 
###Group
Routes can be grouped:
```PHP
$router->group('/api', function ($router) {
  $router->get('/users', 'ControllerUsers::getUsers')
});

$route = $router->go('/api/users');
...
```
groups can be nested:
```PHP
$router->group('/api', function ($router) {
  $router->group('/v1.1' function($router) {
    $router->get('/users', 'ControllerUsers::getUsers')
  });
});
```
###Domain
If your app can de accessed from  multiple hostnames, you can setup personal routes:
```PHP
$router
  ->domain('api.example.com', function ($router) {
    $router->get('/', function () {
      echo 'api';
    });
  })
  ->get('/', function () {
    echo 'main domain';
  });
```

###REST
It so easy to make RESTfull service, define path: 
```PHP
$router->resource('/photo', 'ControllerPhoto', [
  // define param name, default 'id'
  'name' => 'photo'
]);
```
and define controller with specific methods:
```PHP
class ControllerPhoto
{
  public function index()
  {
    // build list
  }
  
  public function create()
  {
    // build form
  }
  
  public function store()
  {
    // save form
  }
  ...
}
```
Next table show conformity between request path and controller methods:

|Verb	|Path					|Action |Call
|-------|-----------------------|-------|-------------------------
|GET	|/photo					|index  |ControllerPhoto::index
|GET	|/photo/create			|create	|ControllerPhoto::create
|POST	|/photo					|store	|ControllerPhoto::store
|GET	|/photo/:photo			|show	|ControllerPhoto::show
|GET	|/photo/:photo/edit	|edit	|ControllerPhoto::edit
|PUT	|/photo/:photo			|update	|ControllerPhoto::update
|DELETE	|/photo/:photo			|destroy|ControllerPhoto::destroy
Note, that if requested method undefined or is not public, Beacon return fallback function
##Options

##Middleware

##Xml
For loading XML file with routes use next code:
```PHP
$router->loadFromXml('/path/to/routes.xml');
```
see [beacon.xml](https://github.com/undercloud/beacon/blob/master/beacon.xml) file specification
