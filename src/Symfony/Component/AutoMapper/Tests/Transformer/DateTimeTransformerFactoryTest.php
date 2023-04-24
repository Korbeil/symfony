<?php

namespace Symfony\Component\AutoMapper\Tests\Transformer;

use Symfony\Component\AutoMapper\MapperMetadata;
use Symfony\Component\AutoMapper\Transformer\CopyTransformer;
use Symfony\Component\AutoMapper\Transformer\DateTimeImmutableToMutableTransformer;
use Symfony\Component\AutoMapper\Transformer\DateTimeMutableToImmutableTransformer;
use Symfony\Component\AutoMapper\Transformer\DateTimeToStringTransformer;
use Symfony\Component\AutoMapper\Transformer\DateTimeTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\StringToDateTimeTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class DateTimeTransformerFactoryTest extends TestCase
{
    public function testGetTransformer()
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(CopyTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('string')], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeToStringTransformer::class, $transformer);

        $transformer = $factory->getTransformer([new Type('string')], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(StringToDateTimeTransformer::class, $transformer);
    }

    public function testGetTransformerImmutable()
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('object', false, \DateTimeImmutable::class)], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeImmutableToMutableTransformer::class, $transformer);
    }

    public function testGetTransformerMutable()
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('object', false, \DateTimeImmutable::class)], $mapperMetadata);

        self::assertNotNull($transformer);
        self::assertInstanceOf(DateTimeMutableToImmutableTransformer::class, $transformer);
    }

    public function testNoTransformer()
    {
        $factory = new DateTimeTransformerFactory();
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer([new Type('string')], [new Type('string')], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('object', false, \DateTime::class)], [new Type('bool')], $mapperMetadata);

        self::assertNull($transformer);

        $transformer = $factory->getTransformer([new Type('bool')], [new Type('object', false, \DateTime::class)], $mapperMetadata);

        self::assertNull($transformer);
    }
}
