<?php

namespace OpxCore\Tests\Container\Fixtures;

class Dependency implements DependencyInterface
{
    public string $info;

    public function __construct(string $info = 'default')
    {
        $this->info = $info;
    }
}