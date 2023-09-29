<?php

namespace Symfony\Component\AutoMapper\Tests\Fixtures;

class Node
{
    /**
     * @var Node
     */
    public $parent;

    /**
     * @var Node[]
     */
    public $childs = [];
}
