<?php

namespace OpxCore\Container\Interfaces;

use Psr\Container\ContainerInterface;

interface Container extends ContainerInterface
{
    /**
     * Register a binding with the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  \Closure|array|null $parameters
     *
     * @return  $this
     */
    public function bind($abstract, $concrete = null, $parameters = null): self;

    /**
     * Register a singleton binding in the container.
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  \Closure|array|null $parameters
     *
     * @return  $this
     */
    public function singleton($abstract, $concrete = null, $parameters = null): self;

    /**
     * Define a contextual binding.
     *
     * @param  string|array $abstract
     * @param  \Closure|array $parameters
     *
     * @return  $this
     */
    public function bindParameters($abstract, $parameters): self;

    /**
     * Register an existing instance in the container.
     *
     * @param  string $abstract
     * @param  mixed $instance
     *
     * @return  $this
     */
    public function instance($abstract, $instance): self;

    /**
     * Alias a type to a different name.
     *
     * @param  string $abstract
     * @param  string $alias
     *
     * @return  $this
     */
    public function alias($abstract, $alias): self;

    /**
     * Resolve the given type from the container.
     *
     * @param  string $abstract
     * @param  array|\Closure|null $parameters
     *
     * @return  mixed
     */
    public function make($abstract, $parameters = null);
}
