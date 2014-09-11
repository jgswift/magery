magery
====

PHP 5.4+ magic interception system using traits

[![Build Status](https://travis-ci.org/jgswift/magery.png?branch=master)](https://travis-ci.org/jgswift/magery)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jgswift/magery/badges/quality-score.png?s=09ecf4d598dfdb7d99070e7ba8a7d197abddfae1)](https://scrutinizer-ci.com/g/jgswift/magery/)
[![Latest Stable Version](https://poser.pugx.org/jgswift/delegatr/v/stable.svg)](https://packagist.org/packages/jgswift/delegatr)
[![License](https://poser.pugx.org/jgswift/delegatr/license.svg)](https://packagist.org/packages/jgswift/delegatr)
[![Coverage Status](https://coveralls.io/repos/jgswift/delegatr/badge.png?branch=master)](https://coveralls.io/r/jgswift/delegatr?branch=master)

## Description

magery provides a trait framework with which to hook into object magic, namely __get, __set, __unset, __isset, and __call.

## Installation

Install via cli using [composer](https://getcomposer.org/):
```sh
php composer.phar require jgswift/magery:0.1.*
```

Install via composer.json using [composer](https://getcomposer.org/):
```json
{
    "require": {
        "jgswift/magery": "0.1.*"
    }
}
```

## Dependency

* php 5.4+

## Usage

### Basic

```php
class Foo
{
    use Magery\Mage;
   
    private $bar;
   
    public function __construct()
    {
        $this->read('bar', function(){
            throw new \Exception('Don\'t touch my bar!');
        });
    }
    
    public function touchBar()
    {
        $this->bar;
    }
}

$foo = new Foo();
$foo->touchBar(); // Fatal error: Uncaught exception 'Exception' with message 'Don't touch my bar!'
```

### Write

A write callback could be registered to protect a variable from being overwritten (even from within the scope of your class)

```php
$this->write('bar', function() {
  throw new \Exception('Don\'t write to my bar!');
});
        
public function writeToBar() {
  $this->bar = 'somethingElse';
}        
        
$foo = new Foo();
$foo->writeToBar(); // Fatal error: Uncaught exception 'Exception' with message 'Don't write to my bar!'        
```

### Read

It is possible to intercept any object property or method with an event.  
Note: Multiple registered events will fire in the order they were added (FIFO) until an event returns a response value. 

```php
class Foo {
    use magery\Mage;

    public function __construct() {
        $this->read('bar', function(){
            return 'baz';
        });

        // Shortcut method
        $this->read('buzz', function() {
            return 'bar';
        });
    }
}
        
        
$foo = new Foo();
echo $foo->bar;     // 'baz'
echo $foo->buzz;    // 'bar'
```

### Exists

```php
class User { 
    private $firstName;
    private $lastName;

    function __construct($firstName, $lastName) { /* ... */ }
}

$user = new User('John', 'Doe');
$user->exists('lastName', function()use(&$c) {
    return isset($this->lastName);
    // do something extra for existence check
});

var_dump(isset($user->lastName)); // true
```

### Remove

```php
$user = new User('Joe','Smith');
$user->remove('name', function()use(&$c) {
    unset($this->name);
    // do something extra for remove
});

unset($user->name);

var_dump(isset($user->name)); // false
```

### Result Caching 

If the event returns a response, this may be cached to reduce execution time on future reads.

```php

public $bar = 'Bill';

public function __construct()
{
  $this->read('bar', function(){
    sleep(1);
    return microtime();
  }, true);     // pass in "true" here (defaults to false)
}
        
        
$foo = new Foo();
var_dump($foo->bar === $foo->bar);   // true
```

### Helper Method Scope

All events are registered globally except the magery function is protected unless otherwise specified.

```php
<?php
class Foo
{
    use Magery\Mage {magery as public;}   // allow public event registration
   
    public $bar;
}

$foo = new Foo();

$foo->read('bar', function(){
    throw new \Exception('Don\'t touch my bar!');
});

$foo->bar;  // Fatal error: Uncaught exception 'Exception' with message 'Don't touch my bar!'
```

### Call

Like read, call magic also may also cache the result to reduce execution time on future calls.

```php
<?php
class Foo
{
    use Magery\Mage;
}

$foo = new Foo();

$foo->call('bar', function() {
    sleep(1);
    return microtime();
},true);

var_dump($foo->bar() === $foo->bar());   // true
```

### Array Accessible Objects

For arrays instead of objects, using a combination of ArrayAccess and the MageAccess trait will provide the same magic opportunities with array syntax

```php
<?php
class Foo implements ArrayAccess {
    use Magery\MageAccess;
}

$foo = new Foo();

$foo->read('bar', function() {
    return 'baz';
});

var_dump($foo['bar']);   // baz
```

### Custom Magic Handler

You can create custom handlers by using traits selectively.  
This example class only includes write magic and all other operations are performed natively without interruption.
Custom objects must use ```Magery\Object``` and you may additionally choose any combination of ```Traits/Read```, ```Traits/Write```, ```Traits/Remove```, ```Traits/Exists``` traits to apply specific magery functionality.

```php
<?php
class Foo
{
    use Magery\Object, Magery\Traits\Write;
}

$foo = new Foo();

$foo->write('bar', function($v){
    $this->baz = $v;
});

$foo->bar = 'somethingImportant';

var_dump($foo->baz);   // somethingImportant
```