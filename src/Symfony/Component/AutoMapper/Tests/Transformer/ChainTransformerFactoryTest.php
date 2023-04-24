<?php

namespace Symfony\Component\AutoMapper\Tests\Transformer;

use Symfony\Component\AutoMapper\MapperMetadata;
use Symfony\Component\AutoMapper\Transformer\ChainTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\CopyTransformer;
use Symfony\Component\AutoMapper\Transformer\TransformerFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class ChainTransformerFactoryTest extends TestCase
{
    public function testGetTransformer()
    {
        $chainTransformerFactory = new ChainTransformerFactory();
        $transformer = new CopyTransformer();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $subTransformer = $this
            ->getMockBuilder(TransformerFactoryInterface::class)
            ->getMock()
        ;

        $subTransformer->expects($this->any())->method('getTransformer')->willReturn($transformer);
        $chainTransformerFactory->addTransformerFactory($subTransformer);

        $transformerReturned = $chainTransformerFactory->getTransformer([], [], $mapperMetadata);

        self::assertSame($transformer, $transformerReturned);
    }

    public function testNoTransformer()
    {
        $chainTransformerFactory = new ChainTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();
        $subTransformer = $this
            ->getMockBuilder(TransformerFactoryInterface::class)
            ->getMock()
        ;

        $subTransformer->expects($this->any())->method('getTransformer')->willReturn(null);
        $chainTransformerFactory->addTransformerFactory($subTransformer);

        $transformerReturned = $chainTransformerFactory->getTransformer([], [], $mapperMetadata);

        self::assertNull($transformerReturned);
    }
}
