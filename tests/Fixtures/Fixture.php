<?php

namespace OpxCore\Tests\Container\Fixtures;

class Fixture
{
    public DependencyInterface $dependency;

    public function __construct(DependencyInterface $dependency)
    {
        $this->dependency = $dependency;
    }
}