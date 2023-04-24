<?php

namespace Symfony\Component\AutoMapper\Tests\Fixtures\Issue425;

use Symfony\Component\AutoMapper\Tests\Fixtures\Issue425\bigint;

class Foo
{
    /** @var bigint[] */
    private array $property = [];

    public function __construct(array $property)
    {
        $this->property = $property;
    }

    public function getProperty(): array
    {
        return $this->property;
    }
}
