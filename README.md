#Beacon - PHP Routing System
[![Build Status](https://travis-ci.org/undercloud/beacon.svg?branch=master)](https://travis-ci.org/undercloud/beacon)

##Features
- Zero dependency
- PCRE pattern path support
- Route groups
- Domain condition support
- HTTPS condition support
- Controller bindings
- RESTful
- Unicode

##ToDO
* wildcard
* auth

##Requirements
PHP 5.4+

##Installation
`composer require undercloud/beacon`

##.htaccess
Pretty URLs with .htaccess:
```
RewriteEngine On
RewriteCond   %{REQUEST_FILENAME}       !-d
RewriteCond   %{REQUEST_FILENAME}       !-f
RewriteRule   ^(.*) index.php?%{QUERY_STRING}
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
Here's a basic usage example:
```PHP
$router
  // any of methods get,post,put or delete
  ->on('/', 'Controller::index')
  // only get method
  ->get('/user/:id', 'ControllerUser::getUser')
  // only post
  ->post('/user/:id', function () { ... })
  // fallback function
  ->otherwise(function() {
    echo 404;
  });

// resolve request
$route = $router->go($_SERVER['REQUEST_URI']);
```

`Beacon\Router::go` method return `Beacon\Route` instance with next methods:

```PHP
// route path
$route->getPath();
// get callback
$route->getCallback();
// get binded params
$route->getParams();
// get middlewares list
$route->getMiddleware();
```

###Unicode paths
Route paths can contain any Unicode character set
```PHP
$router->on('/gâteau-français', function () { ... })
```

###Methods
Complete list of avail route methods
```PHP
/*
 * $path - request path, example /users, support PCRE
 * $call - callback, string 'Controller::action' or Closure
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
For example, route with binded params:
```PHP
$router->on('/user/:id/:name(/:nickname)', 'ControllerUser::getUser');
```
from request:
```PHP
$route = $router->go('/user/78/John');
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
for retrieve params use `$route->getParams()`

###Otherwise
If request cannot be resolved, you can define fallback:
```PHP
$router
  ->otherwise(function(){
    switch(Beacon\RouterError::getErrorCode()){
      case Beacon\RouterError::NO_ERROR:
      /* All fine, only for example, never exists in otherwise block */
      break;

      case Beacon\RouterError::NOT_FOUND_ERROR:
      /* Same as 404 error, cannot find any path for current request */
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
      /* Cannot find implemented method in given REST controller*/
      break;
    }
  });
```

###Controller
You can define controller namespace and bind methods to path:
```PHP
// bind path to controller
$router->controller('/users', 'ControllerUsers');
// resolve
$route = $router->go('/users/get-users');
// will return ControllerUsers::getUsers
$call = $route->getCallback();
```
if requested method undefined or is not public, Beacon return fallback function.

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
  // build list
  public function index()
  {
    ...
  }

  // build form
  public function create()
  {
    ...
  }

  // save form
  public function store()
  {
    ...
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
Note, that if requested method undefined or is not public, Beacon return fallback function.

##Route options
All methods:

* on
* get
* post
* put
* delete
* match
* controller
* group
* domain
* resource

have last argument named `$options`, now it support next params:

* secure - secure flag
* middleware - hold middleware chain

Options defined in parent sections, will be inherited by childs, e.g.:
```PHP
$router->group('/api', function ($router) {
  // now in inherit options defined in group
  $router->get('/users/:id', function() {...});
}, [
  'secure' => true,
  'middleware' => ['MiddlewareAuth','MiddlewareCompressor']
]);
```

You can override inherited, just define personal:
```PHP
$router->group('/api', function ($router) {
  // now in inherit options defined in group
  $router->get('/users/:id', function() {...}, [
    'secure' => false
  ]);
}, [
  'secure' => true,
  'middleware' => ['MiddlewareAuth','MiddlewareCompressor']
]);
```
For defining global pre-setuped options use `Beacon\Router::globals(array $options)`.
```PHP
$router
  ->globals({
    'secure' => true,
    'middleware' => ['MiddlewareAuth']
  })
  // inherit global options
  ->get('/', 'Controller::index')
  // override global
  ->post('/users', 'ControllerUsers::getUsers', [
    'secure' => false
  ])
```

##Middleware chain
Beacon makes it easy to manage the chain of middlewares, look at this example:
```PHP
$router
  ->globals([
    'middleware' => ['MiddlewareAuth']
  ])
  ->group('/api', function ($router) {
    $router->on('/guest', 'ControllerApi::getGuests',[
      // delete global, and append more
      'middleware' => ['del:MiddlewareAuth','add:MiddlewareGuest']
    ]);
    // append middleware
  }, ['middleware' => ['add:MiddlewareApi']]);
```
Now middleware stack for `/api/guest` is `['MiddlewareApi','MiddlewareGuest']`

##Xml
For loading XML file with routes use next code:
```PHP
$router->loadFromXml('/path/to/routes.xml');
```
for more details, see [beacon.xml](https://github.com/undercloud/beacon/blob/master/beacon.xml) file specification

##Error Handling
see [Otherwise section](https://github.com/undercloud/beacon#otherwise)
