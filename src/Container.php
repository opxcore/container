<?php

namespace OpxCore\Container;

use OpxCore\Container\Exceptions\ContainerException;
use OpxCore\Container\Exceptions\NotFoundException;
use OpxCore\Interfaces\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * The current container.
     *
     * @var  static
     */
    protected static $container;

    /**
     * The container's bindings.
     *
     * @var  array
     */
    protected $bindings = [];

    /**
     * The container's instances.
     *
     * @var  array
     */
    protected $instances = [];

    /**
     * The registered aliases.
     *
     * @var  array
     */
    protected $aliases = [];

    /**
     * The parameters for contextual binding.
     *
     * @var  array
     */
    protected $parameters = [];

    /**
     * Get container.
     *
     * @return  \OpxCore\Interfaces\ContainerInterface|static
     */
    public static function getContainer()
    {
        if (static::$container === null) {
            static::$container = new static;
        }

        return static::$container;
    }

    /**
     * Set container.
     *
     * @param  \OpxCore\Interfaces\ContainerInterface|null $container
     *
     * @return  \OpxCore\Interfaces\ContainerInterface|static
     */
    public static function setContainer($container = null)
    {
        return static::$container = $container;
    }

    /**
     * Register a binding in the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  \Closure|array|null $parameters
     *
     * @return  $this
     */
    public function bind($abstract, $concrete = null, $parameters = null): ContainerInterface
    {
        $this->makeBinding($abstract, $concrete, $parameters, false);

        return $this;
    }

    /**
     * Register a singleton binding in the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  \Closure|array|null $parameters
     *
     * @return  $this
     */
    public function singleton($abstract, $concrete = null, $parameters = null): ContainerInterface
    {
        $this->makeBinding($abstract, $concrete, $parameters, true);

        return $this;
    }

    /**
     * Alias a type to a different name.
     *
     * @param  string $abstract
     * @param  string $alias
     *
     * @return  $this
     */
    public function alias($abstract, $alias): ContainerInterface
    {
        $this->aliases[$alias] = $abstract;

        return $this;
    }

    /**
     * Register an existing instance as singleton in the container.
     *
     * @param  string $abstract
     * @param  mixed $instance
     *
     * @return  $this
     */
    public function instance($abstract, $instance): ContainerInterface
    {
        $this->instances[$abstract] = $instance;

        return $this;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws  \Psr\Container\NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws  \Psr\Container\ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return  mixed Entry.
     */
    public function get($id)
    {
        return $this->make($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param  string $id Identifier of the entry to look for.
     *
     * @return  bool
     */
    public function has($id): bool
    {
        return isset($this->bindings[$id]) || isset($this->aliases[$id]) || isset($this->instances[$id]);
    }

    /**
     * Make binding to this container.
     *
     * @param  string $abstract
     * @param  string|\Closure|null $concrete
     * @param  array|\Closure|null $parameters
     * @param  bool $singleton
     *
     * @return  void
     */
    protected function makeBinding($abstract, $concrete, $parameters, $singleton): void
    {
        $this->forget($abstract);

        if ($concrete === null) {
            // In this case we are binding abstract to itself for future resolving dependencies
            $concrete = $abstract;
        }

        if (!$concrete instanceof \Closure) {
            // If concrete is not a Closure, it means we are binding a class name into this
            // container to the abstract type and we will wrap it up inside its own Closure
            // to get more convenience when extending.
            $concrete = $this->makeClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'singleton');

        if ($parameters !== null) {
            $this->parameters[$abstract] = $parameters;
        }
    }

    /**
     * Drop all of the stale instances and aliases.
     *
     * @param  string $abstract
     *
     * @return  void
     */
    public function forget($abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param  string $abstract
     * @param  string $concrete
     *
     * @return  \Closure
     */
    protected function makeClosure($abstract, $concrete): \Closure
    {
        return function ($container, $parameters) use ($abstract, $concrete) {
            /** @var \OpxCore\Container\Container $container */
            if ($abstract === $concrete) {
                return $container->build($concrete, $parameters);
            }

            return $container->make($concrete, $parameters);
        };
    }

    /**
     * Define a contextual binding.
     *
     * @param  string|array $abstract
     * @param  \Closure|array $parameters
     *
     * @return  $this
     */
    public function bindParameters($abstract, $parameters): ContainerInterface
    {
        $this->parameters[$abstract] = $parameters;

        return $this;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string $abstract
     * @param  array|\Closure|null $parameters
     *
     * @return  mixed
     *
     * @throws  \OpxCore\Container\Exceptions\ContainerException
     * @throws  \OpxCore\Container\Exceptions\NotFoundException
     */
    public function make($abstract, $parameters = null)
    {
        $abstract = $this->getAlias($abstract);

        // If an instance of given type exists, it means we registered it as instance
        // or singleton and we will just return it. Parameters are ignored because
        // object was already created and there is no need for them.
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get all registered contextual and overridden parameters
        $parameters = $this->resolveParameters($abstract, $parameters);

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        if ($this->canBuild($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isSingleton($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Check if abstract was registered as singleton.
     *
     * @param  string $abstract
     *
     * @return  bool
     */
    private function isSingleton($abstract): bool
    {
        return isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton'];
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param  string $abstract
     *
     * @return  string
     *
     * @throws  \OpxCore\Container\Exceptions\ContainerException
     */
    private function getAlias($abstract): string
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        if ($this->aliases[$abstract] === $abstract) {
            throw new ContainerException("[{$abstract}] is aliased to itself.");
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Resolve contextual parameters for abstract.
     *
     * @param  string $abstract
     * @param  array|\Closure|null $parameters
     *
     * @return  array
     */
    private function resolveParameters($abstract, $parameters): array
    {
        $resolved = [];

        if (isset($this->parameters[$abstract])) {
            $resolved = $this->resolveParametersClosure($this->parameters[$abstract]);
        }

        if ($parameters !== null) {
            $resolved = array_merge($resolved, $this->resolveParametersClosure($parameters));
        }

        return $resolved;
    }

    /**
     * Resolve parameters closure.
     *
     * @param  \Closure|array $parameters
     *
     * @return  array
     */
    private function resolveParametersClosure($parameters): array
    {
        return $parameters instanceof \Closure
            ? $parameters($this)
            : $parameters;
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param  mixed $concrete
     * @param  string $abstract
     *
     * @return  bool
     */
    private function canBuild($concrete, $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string|\Closure $concrete
     * @param  array $parameters
     *
     * @return  mixed
     *
     * @throws  ContainerException
     * @throws  NotFoundException
     */
    private function build($concrete, $parameters)
    {
        // If the concrete type is a Closure, we will just execute it and return
        // back the result.
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new \ReflectionClass($concrete);

            // If the type is not instantiable, the developer is attempting to resolve
            // an abstract type such as an Interface or Abstract Class and there is
            // no binding registered for the abstractions so we need to bail out.
            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Target [$concrete] is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            // If there are no constructors, that means there are no dependencies then
            // we can just resolve the instances of the objects right away, without
            // resolving any other types or dependencies out of these containers.
            if ($constructor === null) {
                return new $concrete;
            }

            $dependencies = $constructor->getParameters();

            // Once we have all the constructor's parameters we can create each of the
            // dependency instances and then use the reflection instances to make a
            // new instance of this class, injecting the created dependencies in.
            $resolved = $this->resolveDependencies($dependencies, $parameters);

            return $reflector->newInstanceArgs($resolved);

        } catch (\ReflectionException $exception) {
            throw new NotFoundException("Unable to resolve [{$concrete}]. {$exception->getMessage()}");
        }
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param  array $dependencies
     * @param  array $parameters
     *
     * @return  array
     *
     * @throws  \OpxCore\Container\Exceptions\ContainerException
     * @throws  \OpxCore\Container\Exceptions\NotFoundException
     */
    private function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (isset($parameters[$dependency->name])) {
                // If this dependency has a override for this build we will use it
                // as the value.
                $results[] = $parameters[$dependency->name];

            } elseif ($dependency->getClass() !== null) {
                // If class is not null, it means we must to resolve dependency injection
                $results[] = $this->resolveClass($dependency);

            } elseif ($dependency->isDefaultValueAvailable()) {
                // If we goes here, a dependency is a string or some other primitive
                // which we can not resolve but we still can test it for default value.
                $results[] = $dependency->getDefaultValue();

            } else {
                // At last we can say we can not resolve this dependency. We tried.
                throw new ContainerException("{$dependency->name} not set.");
            }
        }

        return $results;
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param  \ReflectionParameter $parameter
     *
     * @return  mixed
     *
     * @throws  \OpxCore\Container\Exceptions\ContainerException
     * @throws  \OpxCore\Container\Exceptions\NotFoundException
     */
    protected function resolveClass(\ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        } catch (ContainerException $exception) {
            // If we can not resolve the class instance, we will check to see if the value
            // has default value.
            try {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
            } catch (\ReflectionException $exception) {
                throw new ContainerException($exception->getMessage());
            }

            throw $exception;
        }
    }
}
