# Beacon - PHP Routing System
[![Build Status](https://travis-ci.org/undercloud/beacon.svg?branch=master)](https://travis-ci.org/undercloud/beacon)

## Features
- Zero dependency
- PCRE pattern path support
- Route groups
- Domain condition support
- HTTPS condition support
- Controller bindings
- RESTful
- Unicode
- Wildcard attributes
- Auth

## Requirements
PHP 5.4+

## Installation
`composer require undercloud/beacon`

## .htaccess
Pretty URLs with .htaccess:
```
RewriteEngine On
RewriteCond   %{REQUEST_FILENAME}       !-d
RewriteCond   %{REQUEST_FILENAME}       !-f
RewriteRule   ^(.*) index.php?%{QUERY_STRING}
```

## Setup
```PHP
// if installed by composer
require '/path/to/vendor/autoload.php';
// if installed manually
require '/path/to/Beacon/Beacon.php';

$router = new Beacon\Router([
  // current hostname
  'host' => $_SERVER['SERVER_NAME'],
  // current http method
  'method' => $_SERVER['REQUEST_METHOD'],
  // optionaly, true if request over https
  'isSecured' => true
]);
```

## Concept
The API is built on the principle of a 
https://en.wikipedia.org/wiki/Fluent_interface

## Define routes
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

### Unicode paths
Route paths can contain any Unicode character set
```PHP
$router->on('/gâteau-français', function () { ... })
```

### Methods
Complete list of avail route methods
```PHP
/*
 * $path - request path, example /users, support PCRE
 * $call - callback, string 'Controller::action' or Closure
 * $methods - array of http methods, example ['post','put']
 */

$router->on($path, $call);
$router->get($path, $call);
$router->post($path, $call);
$router->put($path, $call);
$router->delete($path, $call);
$router->patch($path, $call);
$router->head($path, $call);
$router->match(array $methods, $path, $call);
```

### Params
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
    ->withWhere('id', '/\d+/')
    // empty or invalid nickname will be replaced with 'Guest'
    ->withWhere('nickname', '/[A-Za-z0-9]+/', 'Guest')
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
For retrieve params use `$route->getParams()`

### Wildcard Attributes
Sometimes it is useful to allow the trailing part of the path be anything at all.
To allow arbitrary trailing path segments on a route, call the wildcard() method.
This will let you specify the attribute name under which the arbitrary trailing
values will be stored.
```PHP
$router
  ->on('/foo', function() { ... })
    ->wildcard('card')
  ->on('/bar', function() { ... });

$route = $router->go('/foo/bar/baz/quux');

// ['card' => ['bar','baz','quux']]
$params = $route->getParams();
```

### Otherwise
If request cannot be resolved, you can define fallback:
```PHP
$router
  ->otherwise(function(){
    switch(Beacon\RouterError::getErrorCode()){
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

      case Beacon\RouteError::AUTH_ERROR:
      /* Auth check failed */
      break;
    }
  });
```

### Controller
You can define controller namespace and bind methods to path:
```PHP
// bind path to controller
$router->controller('/users', 'ControllerUsers');
// resolve
$route = $router->go('/users/get-users');
// will return ControllerUsers::getUsers
$call = $route->getCallback();
```
If requested method undefined or is not public, Beacon return fallback function.

### Group
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

### Domain
If your app can de accessed from  multiple hostnames, you can setup personal routes for each domains:
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

### REST
It so easy to make RESTfull service, define path:
```PHP
$router->resource(
  '/photo',
  'ControllerPhoto', 
  // define param name, default 'id'
  $paramName' = 'photo'
);
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

## Route options
All methods:

* on
* get
* post
* put
* delete
* patch
* head
* match
* controller
* group
* domain
* resource

can be additionaly setuped with next methods:

* withSecure - secure settings
* withMiddleware, withoutMiddleware, withoutAnyMiddleware - manage middleware chain
* withAuth - access checker

Options defined in parent sections, will be inherited by childs, e.g.:
```PHP
$router
  ->group('/api', function ($router) {
    // now in inherit options defined in group
    $router->get('/users/:id', function() {...});
  })
    ->withSsecure(true),
    ->withMiddleware(['MiddlewareAuth','MiddlewareCompressor'])
  ->group('/another-group', ...);
```

You can override inherited, just define personal:
```PHP
$router->group('/api', function ($router) {
  // now in inherit options defined in group
  $router->get('/users/:id', function() {...})
    ->withSecure(false);
})
  ->withSecure(true)
  ->withMiddleware(['MiddlewareAuth','MiddlewareCompressor']);
```
For defining global pre-setuped options use `Beacon\Router::globals(array $options)`.
```PHP
$router
  ->globals()
    withSecure(true)
    withMiddleware(['MiddlewareAuth'])
  // inherit global options
  ->get('/', 'Controller::index')
  // override global
  ->post('/users', 'ControllerUsers::getUsers')
    ->withSecure(false)
```

## Middleware chain
Beacon makes it easy to manage the chain of middlewares, look at this example:
```PHP
$router
  ->globals()
    ->withMiddleware('MiddlewareAuth')
  ->on('/', function() { ... })
  ->group('/api', function ($router) {
    $router->on('/guest', 'ControllerApi::getGuests')
      withoutMiddleware('MiddlewareAuth')
      withMiddleware('MiddlewareGuest');

    $router->on('/secure')
      ->withoutAnyMiddleware()
      ->withMiddleware('MiddlewareSecure');
  })
    ->withMiddleware('MiddlewareApi');
```
Now middleware stack for:
   * `/` is `['MiddlewareAuth','MiddlewareApi']`
   * `/api/guest` is `['MiddlewareApi','MiddlewareGuest']`
   * `/api/secure` is `['MiddlewareSecure']`

## Secure
If you wanna routes, that must be handle over https only, setup it like this:
```PHP
$router
  ->get('/pay', 'System::pay')
    ->withSecure(true)
```

## Auth
You can assign callback for access check:
```PHP
$router
  ->get('/dashboard', 'System::dashboard')
  ->withAuth('User::isAdmin')
```
Or for high level methods `group`, `domain`, `controller`, `rest`:
```PHP
$router
  ->group('/api', function(){
    ...
    })
      ->withAuth('Api::checkKey')
```

## Error Handling
see [Otherwise section](https://github.com/undercloud/beacon#otherwise)
