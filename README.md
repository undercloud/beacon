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

##Define

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
