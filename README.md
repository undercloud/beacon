#Beacon - PHP Routing System

##Features
- Zero dependency
- PCRE pattern path support
- Route groups
- Domain condition support
- HTTPS condition support
- REST

##Requirements

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