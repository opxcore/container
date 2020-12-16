<?php

namespace OpxCore\Tests\Container;

use OpxCore\Container\Container;
use OpxCore\Container\Exceptions\ContainerException;
use OpxCore\Container\Exceptions\NotFoundException;
use OpxCore\Tests\Container\Fixtures\Dependency;
use OpxCore\Tests\Container\Fixtures\DependencyHard;
use OpxCore\Tests\Container\Fixtures\DependencyInterface;
use OpxCore\Tests\Container\Fixtures\DependencyNested;
use OpxCore\Tests\Container\Fixtures\Fixture;
use OpxCore\Tests\Container\Fixtures\SimpleFixture;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{

    public function testGetNewContainer(): void
    {
        $container = Container::getContainer();
        $class = get_class($container);
        self::assertEquals(Container::class, $class);

        $oneMoreContainer = Container::getContainer();
        self::assertSame($container, $oneMoreContainer);
    }

    public function testSetContainer(): void
    {
        $container = new Container;

        Container::setContainer($container);

        self::assertSame($container, Container::getContainer());
    }

    public function testBindString(): void
    {
        $container = new Container;
        // string
        $container->bind('test', stdClass::class);
        self::assertTrue($container->has('test'));
        // callable
        $container->bind('test', function () {
            return new stdClass;
        });
        self::assertTrue($container->has('test'));
        // null
        $container->bind('test');
        self::assertTrue($container->has('test'));
    }

    public function testSingleton(): void
    {
        $container = new Container;
        // string
        $container->singleton('test', stdClass::class);
        self::assertTrue($container->has('test'));
        // null
        $container->singleton('test');
        self::assertTrue($container->has('test'));
        // callable
        $container->singleton('test', function () {
            return new stdClass;
        });
        self::assertTrue($container->has('test'));
    }

    public function testAlias(): void
    {
        $container = new Container;
        $container->alias(stdClass::class, 'test');
        self::assertTrue($container->has('test'));

        $container->forget('test');
        self::assertFalse($container->has('test'));
    }

    /**
     * Test recursive alias error alias.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testAliasToItself(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->alias('fixture', 'fixture');
        $this->expectException(ContainerException::class);
        $container->get('fixture');
    }

    public function testInstance(): void
    {
        $container = new Container;
        // string
        $container->instance('test', stdClass::class);
        self::assertTrue($container->has('test'));

        $container->forget('test');
        self::assertFalse($container->has('test'));
    }

    /**
     * Bind simple
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassWithoutConstructor(): void
    {
        /** @var SimpleFixture $fixture */
        $container = new Container;
        $container->bind('fixture', SimpleFixture::class);
        self::assertInstanceOf(SimpleFixture::class, $container->get('fixture'));
    }

    /**
     * Bind simple
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassWithConstructor(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $fixture = $container->get('fixture');
        self::assertEquals('default', $fixture->dependency->info);
    }

    /**
     * Bind simple
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClass(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind(Fixture::class, Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $fixture = $container->get(Fixture::class);
        self::assertEquals('default', $fixture->dependency->info);
    }

    /**
     * Bind simple with parameter not set
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassWithoutParameter(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', Fixture::class);
        $container->bind(DependencyInterface::class, DependencyHard::class);
        $this->expectException(ContainerException::class);
        $container->get('fixture');
    }

    /**
     * Bind simple with parameter object not set
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassWithoutObjectParameter(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', Fixture::class);
        $this->expectException(ContainerException::class);
        $container->get('fixture');
    }

    /**
     * Bind not instantiable
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundNotInstantiable(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', DependencyInterface::class);
        $this->expectException(ContainerException::class);
        $container->get('fixture');
    }

    /**
     * Bind via class name and separately bind parameters.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassAndSeparateParameters(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $container->bindParameters(Dependency::class, ['info' => 'test']);
        $fixture = $container->get('fixture');
        self::assertEquals('test', $fixture->dependency->info);
    }

    /**
     * Bind via class name with parameters.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassWithParameters(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind(DependencyInterface::class, Dependency::class, ['info' => 'test']);
        $container->bind('fixture', Fixture::class);
        $fixture = $container->get('fixture');
        self::assertEquals('test', $fixture->dependency->info);
    }

    /**
     * Bind via class name with parameters closure.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassWithParametersClosure(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind(DependencyInterface::class, Dependency::class, function () {
            return ['info' => 'test'];
        });
        $container->bind('fixture', Fixture::class);
        $fixture = $container->get('fixture');
        self::assertEquals('test', $fixture->dependency->info);
    }

    /**
     * Bind via closure.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundViaClosure(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', static function () {
            return new Fixture(new Dependency('test'));
        });
        $fixture = $container->get('fixture');
        self::assertEquals('test', $fixture->dependency->info);
    }

    /**
     * Bind via closure with parameters.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundViaClosureWithParameters(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', static function (Container $container, $parameters) {
            return new Fixture($container->make(Dependency::class, $parameters));
        }, ['info' => 'test']);
        $fixture = $container->get('fixture');
        self::assertEquals('test', $fixture->dependency->info);
    }

    /**
     * Bind trough alias.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundClassThroughAlias(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $container->alias('fixture', 'fixture_alias');
        $container->alias('fixture_alias', 'another_fixture_alias');
        $fixture = $container->get('another_fixture_alias');
        self::assertEquals('default', $fixture->dependency->info);
    }

    /**
     * Bind nested.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetBoundNested(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->bind('fixture', Fixture::class);
        $container->bindParameters(Fixture::class, [DependencyInterface::class => DependencyNested::class]);
        $container->bindParameters(DependencyNested::class, [DependencyInterface::class => Dependency::class]);
        $container->alias('fixture', 'alias');
        $container->alias('alias', 'alias2');
        $container->alias('alias2', 'alias3');
        $fixture = $container->get('alias3');
        self::assertEquals('default', $fixture->dependency->info);
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testNotBoundException(): void
    {
        $container = new Container;
        $this->expectException(NotFoundException::class);
        $container->make('test');
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetInstance(): void
    {
        $container = new Container;
        $container->instance('dependency', new Dependency('test'));
        /** @var Dependency $dependency */
        $dependency = $container->make('dependency');
        $dependency->info = 'instance test';

        $dependency = $container->make('dependency');
        self::assertEquals('instance test', $dependency->info);
    }

    /**
     * Singleton not created.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetSingletonNotCreated(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->singleton('fixture', Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $fixture = $container->get('fixture');
        self::assertEquals('default', $fixture->dependency->info);

        $fixture->dependency->info = 'singleton test';
        $fixture = $container->get('fixture');
        self::assertEquals('singleton test', $fixture->dependency->info);
    }

    /**
     * Singleton not created.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetSingletonNotCreatedDirect(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->singleton(Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $fixture = $container->get(Fixture::class);
        self::assertEquals('default', $fixture->dependency->info);

        $fixture->dependency->info = 'singleton test';
        $fixture = $container->get(Fixture::class);
        self::assertEquals('singleton test', $fixture->dependency->info);
    }

    /**
     * Singleton not created with parameters.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function testGetSingletonNotCreatedWithParameters(): void
    {
        /** @var Fixture $fixture */
        $container = new Container;
        $container->singleton('fixture', Fixture::class);
        $container->bind(DependencyInterface::class, Dependency::class);
        $container->bindParameters(Dependency::class, ['info' => 'test']);
        $fixture = $container->get('fixture');
        self::assertEquals('test', $fixture->dependency->info);

        $fixture->dependency->info = 'singleton test';
        $fixture = $container->get('fixture');
        self::assertEquals('singleton test', $fixture->dependency->info);
    }
}
