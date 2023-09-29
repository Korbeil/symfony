<?php

namespace Symfony\Component\AutoMapper\Tests\Transformer;

use Symfony\Component\AutoMapper\Transformer\BuiltinTransformer;
use Symfony\Component\AutoMapper\Transformer\MultipleTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class MultipleTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testMultipleTransformer()
    {
        $transformer = new MultipleTransformer([
            [
                'transformer' => new BuiltinTransformer(new Type('string'), [new Type('int')]),
                'type' => new Type('string'),
            ],
            [
                'transformer' => new BuiltinTransformer(new Type('int'), [new Type('string')]),
                'type' => new Type('int'),
            ],
        ]);

        $output = $this->evalTransformer($transformer, '12');

        self::assertSame(12, $output);

        $output = $this->evalTransformer($transformer, 12);

        self::assertSame('12', $output);
    }
}
