<?php

namespace Symfony\Component\AutoMapper\Tests\Transformer;

use Symfony\Component\AutoMapper\Transformer\CopyTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class CopyTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testCopyTransformer()
    {
        $transformer = new CopyTransformer();

        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame('foo', $output);
    }
}
