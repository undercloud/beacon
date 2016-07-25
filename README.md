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
require '/path/to/vendor/autoload.php';

$router = new Beacon\Router(
  // current hostname
  'host'   => $_SERVER['SERVER_NAME'],
  // current http method
  'method' => $_SERVER['REQUEST_METHOD'],
  // optionaly, true if request over https
  'secure' => true
);
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
###Params


###Otherwise

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
