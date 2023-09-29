<?php

namespace Symfony\Component\AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\Ignore;

class FooIgnore
{
    #[Ignore]
    public ?int $id = null;

    public function getId(): int
    {
        return $this->id;
    }
}
