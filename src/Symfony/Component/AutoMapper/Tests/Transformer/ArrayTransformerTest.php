<?php

namespace Symfony\Component\AutoMapper\Tests\Transformer;

use Symfony\Component\AutoMapper\Transformer\ArrayTransformer;
use Symfony\Component\AutoMapper\Transformer\BuiltinTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class ArrayTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testArrayToArray(): void
    {
        $transformer = new ArrayTransformer(new BuiltinTransformer(new Type('string'), [new Type('string')]));
        $output = $this->evalTransformer($transformer, ['test']);

        self::assertEquals(['test'], $output);
    }
}
