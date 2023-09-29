<?php

namespace Symfony\Component\AutoMapper\Tests\Fixtures;

readonly class AddressDTOReadonlyClass
{
    public function __construct(public string $city)
    {
    }
}
