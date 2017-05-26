# Dependency Injection Container

[![Build Status](https://img.shields.io/travis/slince/di/master.svg?style=flat-square)](https://travis-ci.org/slince/di)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/di.svg?style=flat-square)](https://codecov.io/github/slince/di)
[![Total Downloads](https://img.shields.io/packagist/dt/slince/di.svg?style=flat-square)](https://packagist.org/packages/slince/di)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/di.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/di)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/slince/di.svg?style=flat-square)](https://scrutinizer-ci.com/g/slince/di/?branch=master)

This package is a flexible IOC container for PHP with a focus on being lightweight and fast as well as requiring as little 
configuration as possible. It is an implementation of [PSR-11](https://github.com/container-interop/fig-standards/blob/master/proposed/container.md)

## Installation via composer

Add "slince/di": "~2.0" to the require block in your composer.json and then run composer install.

```json
{
    "require": {
        "slince/di": "~2.0"
    }
}
```

Alternatively, require package use composer cli:

```bash
composer require slince/di
```

## Usage

### Creates a container

Get a instance of container like this:

```php
$container = new Slince\Di\Container\Container();
```

Assume some classes and interfaces like so: 

```php
interface ActorInterface
{}

class Actor implements ActorInterface
{}

class Actress implements ActorInterface
{}

class Director
{
    protected $name;
    protected $age;
    pubic function __construct($name, $age)
    {
        $this->name = $name;
        $this->age = $age;
    }
    public static function factory()
    {
        return new static();
    }
}
class Movie
{
    public $name;
    protected $director;
    protected $actor;
    protected $actress;
    
    public function __construct(Director $director, ActorInterface $actor)
    {
        $this->director = $director;
        $this->actor = $actor;
    }
    
    public function setActress(Actress $actress)
    {
        $this->actress = $actress;
    }
}

```
### Injections

The package provides the following ways to define injections

#### Bind an instance

```php
$director = new Director();
$container->instance('director', $director);
var_dump($container->get('director') === $director); //true

// you can also bind it directly without given service name
$container->instance('director', new Director());
```
Container will share the instance, because the container thinks it's a singleton.

#### Bind a callable function

```php
$container->call('director', function(){
    return new Director();
});
var_dump($container->get('director') instanceof Director::class); //true
```
You should provide a valid callable function. It's useful to register a service with factory method.

```php
$container->call('director', [Director::class, 'factory']);
var_dump($container->get('director') instanceof Director::class); //true
```

#### Registers a class definition(Constructor injection, Setter injection,Property injection)

Some times, the dependencies of service are not classes. In this case, you need to provide the container with the parameters 
for the instantiation of the service class.

```php
$container->define('director', Director::class)
    ->setArguments(['name'=>'James', 'age'=>26]);
```
It's also allowed that use argument position.

```php
$container->define('director', Director::class)
    ->setArguments([0=>'James', 1=>26]);
```
> You can omit the class dependencies.


If the current service class depends on a defined service. You can define it like so.

```php
$container->define('movie', Movie::class)->setArguments(['actor'=> new Slince\Di\Reference('actor')]);
```

Setter injection,Property injection.

```php
$container->define('movie', Movie::class)
    ->setMethodCalls(['setActress' => []])
    ->setProperties(['name' => 'foo']);
```
> Since both the Movie `constructor` and the `setActress` depend on the object, the actual arguments are omitted here.


#### Interface/Abstract class injection

```php
$container->bind(ActorInterface::class, Actor::class):
$container->get(ActorInterface::class); //will get a instance of "Actor::class"
```

If the interface has more than one implementations and you want to instantiate different implementation classes when dealing with 
dependencies of different classes; Just provide "BindingContext" for method `bind`

```php
$container->bind(ActorInterface::class, Actor::class, Movie::class):
$container->bind(ActorInterface::class, Actress::class, OtherClass::class):
```

Set different implementation classes in different methods of the same class:

```php
$container->bind(ActorInterface::class, Actor::class, [Movie::class, '__construct']):
$container->bind(ActorInterface::class, Actress::class, [Movie::class, 'setActress']):
```

#### Singleton

```php
$container->define('director', Director::class);
$container->share('director');
```

#### Global Parameters

```php
$container->setParameters([
    'directorName' => 'James',
    'director' => [
        'age' => 26
    ]
]);

//when instantiated
$container->get(Director::class, [
    'name' => '%directorName%',
    'age' => '%director.age%' //Support dot access to deep data
]);
//In the call "define"
$container->define('director', Director::class, [
    'name' => '%directorName%',
    'age' => '%director.age%'
]);
$container->get('director');
```

## License
 
The MIT license. See [MIT](https://opensource.org/licenses/MIT)