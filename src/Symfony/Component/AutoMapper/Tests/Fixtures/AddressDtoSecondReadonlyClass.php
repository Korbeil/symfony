<?php

namespace Symfony\Component\AutoMapper\Tests\Fixtures;

readonly class AddressDtoSecondReadonlyClass
{
    public function __construct(public string $city, public string $postalCode)
    {
    }
}
