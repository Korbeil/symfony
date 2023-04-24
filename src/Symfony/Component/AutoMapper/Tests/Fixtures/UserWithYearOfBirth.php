<?php

namespace Symfony\Component\AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;

class UserWithYearOfBirth
{
    #[Groups('read')]
    private int $id;

    #[Groups('read')]
    public string $name;

    #[Groups('read')]
    public string|int $age;

    public function __construct($id, $name, $age)
    {
        $this->id = $id;
        $this->name = $name;
        $this->age = $age;
    }

    /**
     * @Groups({"read"})
     */
    public function getYearOfBirth()
    {
        return ((int) date('Y')) - ((int) $this->age);
    }
}
