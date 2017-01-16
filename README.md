# Dependency Injection Component

[![Build Status](https://img.shields.io/travis/slince/di/master.svg?style=flat-square)](https://travis-ci.org/slince/di)
[![Coverage Status](https://img.shields.io/codecov/c/github/slince/di.svg?style=flat-square)](https://codecov.io/github/slince/di)
[![Total Downloads](https://img.shields.io/packagist/dt/slince/di.svg?style=flat-square)](https://packagist.org/packages/slince/di)
[![Latest Stable Version](https://img.shields.io/packagist/v/slince/di.svg?style=flat-square&label=stable)](https://packagist.org/packages/slince/di)

依赖注入组件是一个灵活的IOC容器，通过一些配置即可实现类的实例化工作

## 安装

执行下面命令
```
composer require slince/di
```
## 用法

### 创建IOC容器
```
use Slince\Di\Container;

$container = new Container();
```

为了更好的说明api我们假设有有一些类，Movie, Director, Actor, Actress, ActorInterface，代码如下：

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
### 注入定义

声明依赖注入的方式有下面四种

- 直接绑定一个类实例
```
$director = new Director();
$container->instance('director', $director);
var_dump($container->get('director') === $director); //true
```
直接绑定实例的话容器会自动设置为单例模式。

- 设置一个自定义的实例化指令
```
$container->delegate('director', function(){
    return new Director();
});
var_dump($container->get('director') instanceof Director::class); //true
```
参数2接受一个合法的callable结构，该api适用于实例化一些只提供工厂方法的服务类
```
$container->delegate('director', [Director::class, 'factory']);
var_dump($container->get('director') instanceof Director::class); //true
```

- 设置详细的实例化指令（构造器注入，setter注入，property注入）

多数情况下服务类的依赖都是对象，但有些则是非对象并且没有默认值的（即可选）依赖，这时需要告诉容器需要为
该服务类的实例化工作提供哪些参数：

```
$container->define('director', Director::class, ['name'=>'James', 'age'=>26], [], []);
```
> 参数1:别名，参数2:别名实际指向的类，参数3:构造参数，参数4:setter注入，参数5:property注入

提供参数有两种方式，一是通过变量名即例中所示。二种是通过位置索引即只需要给出位置索引与参数的键值对即可；如上例如果
只需要设置导演的年纪则可以这样写，这对需要跳过对象依赖只设置非对象依赖非常有用。

```
$container->define('director', Director::class, [1=>26]); //参数age是第二个参数，即键值设置为1
```

如果当前服务类依赖一个已定义的服务可以使用Reference引用。
```
$container->define('movie', Movie::class, ['actor'=> new Reference('actor')]);
```


setter注入：需要额外告诉容器setter方法，如下例;因为Movie的构造器与`setActress`依赖的都是对象，故在此省略了实际参数。

```
$container->define('movie', Movie::class, [], ['setActress' => []], []);
```


property注入：在参数5设置该类的属性名与参数值的键值对即可。


- 设置别名与可实例化类的指向关系

```
$container->bind('director', Director::class):
$container->get('director');
```
> 旧版本`alias`设计与`bind`重复故新版本中已经废弃并在将来版本中移除

如果直接从容器中获取没有经过预定义的别名，则容器会认为该别名并非别名而是一个可实例化的类名，并以此为别名创建一条指向自身
的定义，因此如果不想纯粹的设置别名也可以直接从容器中获取类的实例:
```
$container->get(Director::class);
```

接口注入：有些依赖并不是可实例化的类而是interface或者abstract class，因此需要告诉容器如何解决这些不可实例化的依赖
```
$container->bind(ActorInterface::class, Actor::class):
```
如果接口有多个实现类并且希望在处理不同类的依赖时实例化不同的实现类，那么需要绑定上下文：
```
$container->bind(ActorInterface::class, Actor::class, Movie::class):
$container->bind(ActorInterface::class, Actress::class, OtherClass::class):
```
在同一个类的不同方法里设置不同的实现类：
```
$container->bind(ActorInterface::class, Actor::class, [Movie::class, '__construct']):
$container->bind(ActorInterface::class, Actress::class, [Movie::class, 'setActress']):
```

以上声明注入定义的方法在使用两参数的情况下皆可使用`set`代替：
```
$container->set('director', new Director());
$container->set('director', Director::class);
$container->set('director', function(){
    return  new Director();
});
//define方法替换方式有所变化
$container->set('director', new Define(Director::class));
```

### 设置单例

```
$container->set('director', Director::class);
$container->share('director');
```
或者直接
```
$container->set('director', Director::class, true);
```
旧版本中`share`的使用方式已经废弃并在将来版本中移除，建议使用set方法代替。

### 全局参数
```
$container->setParameters([
    'directorName' => 'James',
    'director' => [
        'age' => 26
    ]
]);

//在实例化时传入
$container->get(Director::class, [
    'name' => '%directorName%',
    'age' => 'director.age' //支持点号访问深层数据
]);
//在define时传入
$container->define('director', Director::class, [
    'name' => '%directorName%',
    'age' => 'director.age' //支持点号访问深层数据
]);
$container->get('director');
```