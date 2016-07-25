#Beacon - PHP Routing System
[![Build Status](https://travis-ci.org/undercloud/beacon.svg?branch=master)](https://travis-ci.org/undercloud/beacon)
##Features
- Zero dependency
- PCRE pattern path support
- Route groups
- Domain condition support
- HTTPS condition support
- REST

##Requirements
PHP 5.4+
##Install

##Setup
```PHP
require '/path/to/vendor/autoload.php';

$router = new Beacon\Router(
  'host'   => $_SERVER['SERVER_NAME'],
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

##Options

##Middleware

##Xml

##Handle Errors
