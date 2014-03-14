magery
====

PHP 5.4+ magic interception system using traits

[![Build Status](https://travis-ci.org/jgswift/magery.png?branch=master)](https://travis-ci.org/jgswift/magery)

## Installation

Install via [composer](https://getcomposer.org/):
```sh
php composer.phar require jgswift/magery:dev-master
```

## Usage

Magery allows you to attach global hooks to the magic methods: __get, __set, __unset, __isset, and __call.

```php
<?php
class Foo
{
    use Magery\Mage;
   
    private $bar;
   
    public function __construct()
    {
        $this->magery('read', 'bar', function(){
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

Or you can register a write event to protect the variable from being overwritten (even from within the scope of your class)

```php
$this->magery('write', 'bar', function(){
  throw new \Exception('Don\'t write to my bar!');
});
        
public function writeToBar()
{
  $this->bar = 'somethingElse';
}        
        
$foo = new Foo();
$foo->writeToBar(); // Fatal error: Uncaught exception 'Exception' with message 'Don't write to my bar!'        
```

It is possible to intercept any object property or method with a magic spell (event).  Note: Multiple registered events will fire in the order they were added (FIFO) until an event returns a response value. 

```php

public $bar = 'Bill';

public function __construct()
{
  $this->magery('read', 'bar', function(){
    return 'Baz';
  });
}
        
        
$foo = new Foo();
echo $foo->bar; // "Baz"
```

If the event returns a response, this may be cached to reduce execution time on future reads.

```php

public $bar = 'Bill';

public function __construct()
{
  $this->magery('read','bar', function(){
    sleep(1);
    return microtime();
  }, true);     // pass in "true" here (defaults to false)
}
        
        
$foo = new Foo();
var_dump($foo->bar === $foo->bar);   // true
```

All events are registered globally but the magery function is protected unless otherwise specified.

```php
<?php
class Foo
{
    use Magery\Mage {magery as public;}   // allow public event registration
   
    public $bar;
}

$foo = new Foo();

$foo->magery('read', 'bar', function(){
    throw new \Exception('Don\'t touch my bar!');
});

$foo->bar;  // Fatal error: Uncaught exception 'Exception' with message 'Don't touch my bar!'
```

Like read, call magic also may also cache the result to reduce execution time on future calls.

```php
<?php
class Foo
{
    use Magery\Mage;
}

$foo = new Foo();

$foo->magery('call', 'bar', function(){
    sleep(1);
    return microtime();
},true);

var_dump($foo->bar() === $foo->bar());   // true
```

For arrays instead of objects, using a combination of ArrayAccess and the MageAccess trait will provide the same magic opportunities with array syntax

```php
<?php
class Foo implements ArrayAccess
{
    use Magery\MageAccess;
}

$foo = new Foo();

$foo->magery('get', 'bar', function(){
    return 'Baz';
});

var_dump($foo['bar']);   // Baz
```

You can create custom mages with selective magic.  This example class only includes write magic and all other operations are performed natively without interruption.

```php
<?php
class Foo
{
    use Magery\Object, Magery\Set;
}

$foo = new Foo();

$foo->magery('set', 'bar', function($v){
    $this->baz = $v;
});

$foo->bar = 'somethingImportant';

var_dump($foo->baz);   // somethingImportant
```