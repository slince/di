# Dependency Injection Component

[![Build Status](https://img.shields.io/travis/slince/di/master.svg?style=flat-square)](https://travis-ci.org/slince/di)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/di.svg?style=flat-square)](https://codecov.io/github/slince/di)
[![Total Downloads](https://img.shields.io/packagist/dt/slince/di.svg?style=flat-square)](https://packagist.org/packages/slince/di)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/di.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/di)

This package is a flexible IOC container for PHP with a focus on being lightweight and fast as well as requiring as little configuration as possible.[Simplified Chinese](./README-zh_CN.md)

## Installation via composer
Add "slince/di": "~1.0" to the require block in your composer.json and then run composer install.
```
{
    "require": {
        "slince/di": "~1.0"
    }
}
```
Alternatively, require package use composer cli:
```
composer require slince/di
```
## Basic Usage

### Get IOC container
```
use Slince\Di\Container;

$container = new Container();
```
For illustration，we'll start some class and interface like this: 

```
interface ActorInterface
{
}

class Actor implements ActorInterface
{
}

class Actress implements ActorInterface
{
}

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

The package provides the following four ways to define injections

- Bind instance

```
$director = new Director();
$container->instance('director', $director);
var_dump($container->get('director') === $director); //true
```
Container will share instance by default.

- Bind callable
```
$container->delegate('director', function(){
    return new Director();
});
var_dump($container->get('director') instanceof Director::class); //true
```
delegate expects a callable structure at argument 2;It is useful for instantiating some service classes that provide factory classes/methods.
```
$container->delegate('director', [Director::class, 'factory']);
var_dump($container->get('director') instanceof Director::class); //true
```

- Set the detailed instantiation directives(Constructor injection，Setter injection,Property injection)

In most cases, the dependencies of service classes are objects, but others are non-objects and have no default (ie, optional) dependencies. 
In this case, you need to tell the container which parameters to provide for the instantiation of the service class:

```
$container->define('director', Director::class, ['name'=>'James', 'age'=>26], [], []);
```
> Parameter 1: alias, parameter 2: class, parameter 3: constructor parameters, 
> parameter 4: setter injection, parameter 5: property injection

There are two ways to provide parameters, one by variable name as shown in the example. The second is through the argument position 
that only need to give the position and argument; as in the case if you only need to set the director of the age.
This is useful for setting non-object dependencies only if you want to skip object dependencies.

```
$container->define('director', Director::class, [1=>26]); //The parameter age is the second parameter, the key value is set to 1
```

"Reference" can be used if the current service class depends on a defined service.
```
$container->define('movie', Movie::class, ['actor'=> new Reference('actor')]);
```

Setter injection: The setter method is required, as in the following example: 
```
$container->define('movie', Movie::class, [], ['setActress' => []], []);
```
> Since both the Movie constructor and the setActress depend on the object, the actual arguments are omitted here.


Property injection: In argument 5, set the property name and value of the key-value pair.


- Bind an alias to an instantiable class

```
$container->bind('director', Director::class):
$container->get('director');
```
> Older versions of `alias` were designed to be duplicated with` bind` so new versions are deprecated and removed in future releases

If you do not get a predefined alias directly from the container,the container will consider that the alias is not an alias but an instantiable class，
And use this as an alias to create a definition of their own, so if you do not want to set the alias can also be obtained directly from the 
container instance of the class:
```
$container->get(Director::class);
```

Interface injection: Some dependencies are not instantiable classes but interfaces or abstract classes, so you need to tell the container 
how to resolve these non-instantiable dependencies
```
$container->bind(ActorInterface::class, Actor::class):
```
If an interface has more than one implementation class and wishes to instantiate different implementation classes when dealing with 
dependencies of different classes, then the binding context：
```
$container->bind(ActorInterface::class, Actor::class, Movie::class):
$container->bind(ActorInterface::class, Actress::class, OtherClass::class):
```
Set different implementation classes in different methods of the same class:
```
$container->bind(ActorInterface::class, Actor::class, [Movie::class, '__construct']):
$container->bind(ActorInterface::class, Actress::class, [Movie::class, 'setActress']):
```

The above method of declaring the injection definition can be replaced with `set` if two parameters are used:
```
$container->set('director', new Director());
$container->set('director', Director::class);
$container->set('director', function(){
    return  new Director();
});
//There are some changes to the define method
$container->set('director', new Define(Director::class));
```

### Set singleton

```
$container->set('director', Director::class);
$container->share('director');
```
Or directly
```
$container->set('director', Director::class, true);
```
> The use of `share` in older versions is deprecated and will be removed in a future release. The use of the set method is recommended.

### Global Parameters
```
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