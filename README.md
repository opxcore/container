# Dependency injection container

[![Build Status](https://www.travis-ci.com/opxcore/container.svg?branch=master)](https://www.travis-ci.com/opxcore/container)
[![Coverage Status](https://coveralls.io/repos/github/opxcore/container/badge.svg?branch=master)](https://coveralls.io/github/opxcore/container?branch=master)
[![Latest Stable Version](https://poser.pugx.org/opxcore/container/v/stable)](https://packagist.org/packages/opxcore/container)
[![Total Downloads](https://poser.pugx.org/opxcore/container/downloads)](https://packagist.org/packages/opxcore/container)
[![License](https://poser.pugx.org/opxcore/container/license)](https://packagist.org/packages/opxcore/container)

# Introduction

The dependency injection container is a powerful tool for managing class dependencies and performing dependency
injection. Class dependencies are "injected" into the class via the constructor and resolved by the container.

### Example:

```php
class Controller
{
    protected Repository $repository;
    
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }
}
```

Calling `$container->make(Controller::class)` would be equal to `new Controller(new Repository)`. This amazing feature
resolves all dependency injections automatically with _zero-config_. For this example if
`Repository` have its own dependency, it will be resolved the same.

# Installing

`composer require opxcore/container`

# Creating

You can create a container several ways:

```php
$container = Container::setContainer(new Container);
``` 

or

```php
$container = Container::getContainer();
```

In all of this cases `Container::getContainer()` will always return the same container instance.

If you want to create and handle a container (or several containers) by yourself just use
`$container = new Container` and handle this container instance as you want.

# Registering a binding with the container

Basic binding to container looks like:

```php
$container->bind($abstract, $concrete, $parameters);
```

`$abstract` is a string containing class name or shorthand for a name to be resolved.

`$concrete` is a string containing class name or `callable` returning object for a name to be resolved to. Container
instance and parameters will be passed to callable during resolving.

`$parameters` is an array or callable returning array with parameters used for resolving (see below). Default value
is `null` means no parameters will be bound.

## Examples:

### Simple usage

```php
$container->bind('logger', Logger::class);

// New instance of Logger with resolved dependencies will be retuened.
$logger = $container->make('logger');
```

### Binding interfaces to its realizations

We have `Controller` class what depends on some `RepositoryInterface` and `FileRepository` implementing it:

```php
class Controller
{
    protected RepositoryInterface $repository;
    
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
```

```php
class FileRepository implements RepositoryInterface
{
    protected string $path;
    
    public function __construct(string $path)
    {
        $this->path = $path;
    }
}
```

Now we bind `FileRepository::class` to `RepositoryInterface::class` so than some class depends on `RepositoryInterface`
it will be resolved to `FileRepository` and `path` argument will be passed into with specified value.

```php
$container->bind(RepositoryInterface::class, FileRepository::class, ['path'=>'/data/storage']);

$controller = $container->make(Controller::class);
```

## Binding parameters

You can bind parameters into the container fo resolving. It can be used as primitives binding or class binding:

```php
// Than FileRepository dependencies would be resolving, given value would be passed to `path` attribute.
$container->bindParameters(FileRepository::class, ['path' => '/data/storage']);

// Than Controller would be resolved a FileRepository would be given as RepositoryInterface dependency.
$container->bindParameters(Controller::class, [RepositoryInterface::class => FileRepository::class]);
```

## Singleton

The singleton method binds a class or interface into the container that should be resolved once. First time it will be
resolved and stored in the container, so other times the same object instance will be returned on subsequent calls into
the container.

```php
$container->singleton(RepositoryInterface::class, FileRepository::class, ['path'=>'/data/storage']);

// Each time when RepositoryInterface needs to be resolved
// the same instance of FileRepository would be given.
$repository = $container->make(RepositoryInterface::class);
```

## Alias

Alias is another name for resolving.

```php
$container->singleton(RepositoryInterface::class, FileRepository::class, ['path'=>'/data/storage']);
$container->alias(RepositoryInterface::class, 'repo');

// This would return FileRepository instance.
$container->make('repo');
```

## Instance

You can register any object or value into the container.

```php
$container->instance('path', '/data/storage');
// '/data/storage' would be returned
$container->make('path');

$container->instance(RepositoryInterface::class, new FileRepository('/data/storage'));
// FileRepository would be returned
$container->make(RepositoryInterface::class);
```

# Resolving

## make

order:

1. Check for alias
2. Check for instance
3. Making