<?php

namespace OpxCore\Tests\Container\Fixtures;

class DependencyHard implements DependencyInterface
{
    public string $info;

    public function __construct(string $info)
    {
        $this->info = $info;
    }
}