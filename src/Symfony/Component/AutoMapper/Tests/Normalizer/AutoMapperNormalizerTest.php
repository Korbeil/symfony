<?php

namespace Symfony\Component\AutoMapper\Tests\Normalizer;

use Symfony\Component\AutoMapper\AutoMapperInterface;
use Symfony\Component\AutoMapper\AutoMapperRegistryInterface;
use Symfony\Component\AutoMapper\MapperContext;
use Symfony\Component\AutoMapper\MapperInterface;
use Symfony\Component\AutoMapper\Normalizer\AutoMapperNormalizer;
use Symfony\Component\AutoMapper\Tests\Fixtures;
use Symfony\Component\AutoMapper\Tests\AutoMapperBase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AutoMapperNormalizerTest extends AutoMapperBase
{
    protected AutoMapperNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new AutoMapperNormalizer($this->autoMapper);
    }

    public function testNormalize(): void
    {
        $object = new Fixtures\User(1, 'Jack', 37);
        $expected = ['id' => 1, 'name' => 'Jack', 'age' => 37];

        $normalized = $this->normalizer->normalize($object);
        self::assertIsArray($normalized);
        self::assertEquals($expected['id'], $normalized['id']);
        self::assertEquals($expected['name'], $normalized['name']);
        self::assertEquals($expected['age'], $normalized['age']);
    }

    public function testDenormalize(): void
    {
        $source = ['id' => 1, 'name' => 'Jack', 'age' => 37];

        /** @var Fixtures\User $denormalized */
        $denormalized = $this->normalizer->denormalize($source, Fixtures\User::class);
        self::assertInstanceOf(Fixtures\User::class, $denormalized);
        self::assertEquals($source['id'], $denormalized->getId());
        self::assertEquals($source['name'], $denormalized->name);
        self::assertEquals($source['age'], $denormalized->age);
    }

    public function testSupportsNormalization(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(['foo']));
        self::assertFalse($this->normalizer->supportsNormalization('{"foo":1}'));

        $object = new Fixtures\User(1, 'Jack', 37);
        self::assertTrue($this->normalizer->supportsNormalization($object));

        $stdClass = new \stdClass();
        $stdClass->id = 1;
        $stdClass->name = 'Jack';
        $stdClass->age = 37;
        self::assertFalse($this->normalizer->supportsNormalization($stdClass));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(['foo' => 1], 'array'));
        self::assertTrue($this->normalizer->supportsDenormalization(['foo' => 1], 'json'));

        $user = ['id' => 1, 'name' => 'Jack', 'age' => 37];
        self::assertTrue($this->normalizer->supportsDenormalization($user, Fixtures\User::class));
        self::assertTrue($this->normalizer->supportsDenormalization($user, \stdClass::class));
    }

    public function testNormalizeWithNoReturnType(): void
    {
        $object = new Fixtures\UserWithYearOfBirth(1, 'Foo', 37);
        $expected = ['id' => 1, 'name' => 'Foo', 'age' => 37, 'yearOfBirth' => (((int) date('Y')) - 37)];

        $normalized = $this->normalizer->normalize($object, null, ['groups' => ['read']]);
        self::assertIsArray($normalized);
        self::assertEquals($expected['id'], $normalized['id']);
        self::assertEquals($expected['name'], $normalized['name']);
        self::assertEquals($expected['age'], $normalized['age']);
        self::assertEquals($expected['yearOfBirth'], $normalized['yearOfBirth']);
    }

    public function testItUsesSerializerContext(): void
    {
        $normalizer = new AutoMapperNormalizer(
            new class() implements AutoMapperInterface, AutoMapperRegistryInterface {
                public function map(null|array|object $source, string|array|object $target, array $context = []): null|array|object
                {
                    return $context;
                }

                public function getMapper(string $source, string $target): MapperInterface
                {
                    throw new \RuntimeException('unexpected');
                }

                public function hasMapper(string $source, string $target): bool
                {
                    return true;
                }
            }
        );

        $context = $normalizer->normalize(new Fixtures\User(1, 'Jack', 37), 'array', [
            AbstractNormalizer::GROUPS => ['foo'],
            AbstractNormalizer::ATTRIBUTES => ['foo'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['foo'],
            AbstractNormalizer::OBJECT_TO_POPULATE => 'some-object',
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => 'circular-reference-handler',
            'custom-context' => 'some custom context',
            MapperContext::ALLOWED_ATTRIBUTES => 'some ignored context',
        ]);

        self::assertSame(
            [
                MapperContext::GROUPS => ['foo'],
                MapperContext::ALLOWED_ATTRIBUTES => ['foo'],
                MapperContext::IGNORED_ATTRIBUTES => ['foo'],
                MapperContext::TARGET_TO_POPULATE => 'some-object',
                MapperContext::CIRCULAR_REFERENCE_LIMIT => 1,
                MapperContext::CIRCULAR_REFERENCE_HANDLER => 'circular-reference-handler',
                'custom-context' => 'some custom context',
            ],
            $context
        );
    }
}
