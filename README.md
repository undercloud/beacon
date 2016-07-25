#Beacon - PHP Routing System
[![Build Status](https://travis-ci.org/undercloud/beacon.svg?branch=master)](https://travis-ci.org/undercloud/beacon)
##Features
- Zero dependency
- PCRE pattern path support
- Route groups
- Domain condition support
- HTTPS condition support
- Controller bindings
- REST

##Requirements
PHP 5.4+
##Install

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
  'secure' => true
);

// retrieve current request path if unknown, example
if ($pos = strpos($_SERVER['REQUEST_URI'],'?')) {
	$path = substr($_SERVER['REQUEST_URI'], 0, $pos);
} else {
	$path = $_SERVER['REQUEST_URI'];
}
```
##Define routes

```PHP
$router
  ->on()
  ->get()
  ->post('/')
  ->match(['post','put'], '')
  ->otherwise(function() { ... })
```
###Methods
Complete list of avail route methods
* $router->on($path, $call [, $options]);
* $router->get($path, $call [, $options]);
* $router->post($path, $call [, $options]);
* $router->put($path, $call [, $options]);
* $router->delete($path, $call [, $options]);
* $router->head($path, $call [, $options]);
* $router->options($path, $call [, $options]);
* $router->connect($path, $call [, $options]);
* $router->patch($path, $call [, $options]);
* $router->match(array $methods, $path, $call [, $options]);
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

###Group

###Domain

###REST
```PHP
$router->resource('/photo', 'ControllerPhoto', [
  // define param name, default 'id'
  'name' => 'photo'
]);
```
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
  
  ...
}
```

|Verb	|Path					|Action |Call
|-------|-----------------------|-------|-------------------------
|GET	|/photo					|index  |ControllerPhoto::index
|GET	|/photo/create			|create	|ControllerPhoto::create
|POST	|/photo					|store	|ControllerPhoto::store
|GET	|/photo/:photo			|show	|ControllerPhoto::show
|GET	|/photo/:photo/edit	|edit	|ControllerPhoto::edit
|PUT	|/photo/:photo			|update	|ControllerPhoto::update
|DELETE	|/photo/:photo			|destroy|ControllerPhoto::destroy
##Options

##Middleware

##Xml

##Handle Errors
See \#otherwise
