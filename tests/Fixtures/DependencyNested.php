<?php

namespace OpxCore\Tests\Container\Fixtures;

class DependencyNested implements DependencyInterface
{
    public string $info;

    public function __construct(DependencyInterface $dependency)
    {
        $this->info = $dependency->info;
    }
}