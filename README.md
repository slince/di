# Dependency Injection Container

[![Build Status](https://img.shields.io/github/actions/workflow/status/slince/di/test.yml?style=flat-square)](https://github.com/slince/di/actions)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/di.svg?style=flat-square)](https://codecov.io/github/slince/di)
[![Total Downloads](https://img.shields.io/packagist/dt/slince/di.svg?style=flat-square)](https://packagist.org/packages/slince/di)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/di.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/di)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/slince/di.svg?style=flat-square)](https://scrutinizer-ci.com/g/slince/di/?branch=master)

This package is a flexible IOC container for PHP with a focus on being lightweight and fast as well as requiring as little 
configuration as possible. It is an implementation of [PSR-11](https://github.com/container-interop/fig-standards/blob/master/proposed/container.md)

## Installation

Install via composer.

```json
{
    "require": {
        "slince/di": "^3.0"
    }
}
```

Alternatively, require package use composer cli:

```bash
composer require slince/di ^3.0
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

class Bar
{
    public $foo;
    public $baz;
    
    public function __construct($foo, $baz)
    {
        $this->foo = $foo;
        $this->baz = $baz;
    }
}

$container = new Slince\Di\Container();

$container->register(Acme\Foo::class);
$foo = $container->get(Acme\Foo::class);

var_dump($foo instanceof Acme\Foo);      // true
var_dump($foo->bar instanceof Acme\Bar); // true
```

### Make Service References

```php
$container->register('bar', Acme\Bar::class);
$container->register('foo', Acme\Foo::class)
    ->addArgument(new Slince\Di\Reference('bar')); //refer to 'bar'
    
var_dump($container->get('bar') === $container->get('foo')->bar));    // true
```

### Use a Factory to Create Services

Suppose you have a factory that configures and returns a new `NewsletterManager` object 
by calling the static `createNewsletterManager()` method:

```php
class NewsletterManagerStaticFactory
{
    public static function createNewsletterManager($parameter)
    {
        $newsletterManager = new NewsletterManager($parameter);

        // ...

        return $newsletterManager;
    }
}
```

```php
// call the static method
$container->register(
    NewsletterManager::class, 
    array(NewsletterManagerStaticFactory::class, 'createNewsletterManager')
)->addArgument('foo');

```
If your factory is not using a static function to configure and create your service, but a regular method, 
you can instantiate the factory itself as a service too. 

```php
// call a method on the specified factory service
$container->register(NewsletterManager::class, [
    new Reference(NewsletterManagerFactory::class),
    'createNewsletterManager'
]);
```

### Create Service Aliases

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

- Autowiring

```php
$container->setDefaults([
    'autowire' => false,
]);
$container->register('foo', Acme\Foo::class)
    ->addArgument(new Acme\Bar());  // You have to provide $bar
    
var_dump($container->get('foo') instanceof Acme\Foo::class);  // true
```

### Container Parameters

```php
$container->setParameters([
    'foo' => 'hello',
    'bar' => [
        'baz' => 'world'
    ]
]);

$container->register('bar', Acme\Bar::class)
     ->setArguments([
        'foo' => $container->getParameter('foo'),
        'baz' => $container->getParameter('bar.baz')
    ]);

$bar = $container->get('bar');
var_dump($bar->foo);  // hello
var_dump($bar->bar); // world
```

### Work with Service Tags

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
