# Dependency Injection Container

[![Build Status](https://img.shields.io/travis/slince/di/master.svg?style=flat-square)](https://travis-ci.org/slince/di)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/di.svg?style=flat-square)](https://codecov.io/github/slince/di)
[![Total Downloads](https://img.shields.io/packagist/dt/slince/di.svg?style=flat-square)](https://packagist.org/packages/slince/di)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/di.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/di)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/slince/di.svg?style=flat-square)](https://scrutinizer-ci.com/g/slince/di/?branch=master)

This package is a flexible IOC container for PHP with a focus on being lightweight and fast as well as requiring as little 
configuration as possible. It is an implementation of [PSR-11](https://github.com/container-interop/fig-standards/blob/master/proposed/container.md)

## Installation via composer

Add "slince/di": "^3.0" to the require block in your composer.json and then run composer install.

```json
{
    "require": {
        "slince/di": "^3.0"
    }
}
```

Alternatively, require package use composer cli:

```bash
composer require slince/di
```

## Usage

Container is dependency injection container. It allows you to implement the dependency injection design pattern meaning that you can decouple your class dependencies and have the container inject them where they are needed.

```php
namespace Acme;

class Foo
{
   /**
     * @var \Acme\Bar
     */
    public $bar;

    /**
     * Construct.
     */
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}

$container = new Slince\Di\Container();

$container->register(Acme\Foo::class);
$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
```

### Alias

```php
$container->register(Acme\Foo::class);
$container->setAlias('foo-alias', Acme\Foo::class);
$foo = $container->get('foo-alias');

var_dump($foo instanceof Acme\Foo);      // true
```

### Configure container 

- Singleton

```php
$container->setDefaults([
    'share' => false
]);
$container->register('foo', Acme\Foo::class);
var_dump($container->get('foo') === $container->get('foo'));      // false
```

- Autowire

```php
$container->setDefaults([
    'autowire' => false,
]);
$container->register('foo', Acme\Foo::class)
    ->addArgument(new Bar());  // You have to provide $bar
    
var_dump($container->get('foo') instanceof Acme\Foo::class);  // true
```

### Global Parameters

```php
class Acme;

class Bar
{
    protected $foo;
    protected $baz;
    
    public function __construct($foo, $baz)
    {
        $this->foo = $foo;
        $this->baz = $baz;
    }
    
    public function getFoo()
    {
        return $this->foo;
    }
    public function getBaz()
    {
        return $this->baz;
    }
}

$container->setParameters([
    'foo' => 'hello',
    'bar' => [
        'baz' => 'world'
    ]
]);

$container->register('bar', Acme\Bar::class)
     ->setArguments([
        'foo' => '%foo%',
        'baz' => '%bar.baz%'
    ]);

$bar = $container->get('bar');
var_dump($bar->getFoo());  // hello
var_dump($bar->getBaz()); //world
```

### Definition tag

```php
$container->register('foo')->addTag('my.tag', array('hello' => 'world'));

$serviceIds = $container->findTaggedServiceIds('my.tag');

foreach ($serviceIds as $serviceId => $tags) {
    foreach ($tags as $tag) {
        echo $tag['hello'];
    }
}
```
## License
 
The MIT license. See [MIT](https://opensource.org/licenses/MIT)