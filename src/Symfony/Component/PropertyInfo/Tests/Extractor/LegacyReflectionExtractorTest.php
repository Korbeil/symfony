<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo\Tests\Extractor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Tests\Fixtures\DefaultValue;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Php71Dummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Php71DummyExtended2;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Php74Dummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Php7Dummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Php7ParentDummy;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class LegacyReflectionExtractorTest extends TestCase
{
    private ReflectionExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ReflectionExtractor();
    }

    /**
     * @dataProvider typesProvider
     */
    public function testExtractors($property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property, []));
    }

    public static function typesProvider()
    {
        return [
            ['a', null],
            ['b', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')]],
            ['c', [new Type(Type::BUILTIN_TYPE_BOOL)]],
            ['d', [new Type(Type::BUILTIN_TYPE_BOOL)]],
            ['e', null],
            ['f', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable'))]],
            ['donotexist', null],
            ['staticGetter', null],
            ['staticSetter', null],
            ['self', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy')]],
            ['realParent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')]],
            ['date', [new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)]],
            ['dates', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class))]],
        ];
    }

    /**
     * @dataProvider php7TypesProvider
     */
    public function testExtractPhp7Type(string $class, string $property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypes($class, $property, []));
    }

    public static function php7TypesProvider()
    {
        return [
            [Php7Dummy::class, 'foo', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true)]],
            [Php7Dummy::class, 'bar', [new Type(Type::BUILTIN_TYPE_INT)]],
            [Php7Dummy::class, 'baz', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))]],
            [Php7Dummy::class, 'buz', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\Php7Dummy')]],
            [Php7Dummy::class, 'biz', [new Type(Type::BUILTIN_TYPE_OBJECT, false, Php7ParentDummy::class)]],
            [Php7Dummy::class, 'donotexist', null],
            [Php7ParentDummy::class, 'parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, \stdClass::class)]],
        ];
    }

    /**
     * @dataProvider php71TypesProvider
     */
    public function testExtractPhp71Type($property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Php71Dummy', $property, []));
    }

    public static function php71TypesProvider()
    {
        return [
            ['foo', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)]],
            ['buz', [new Type(Type::BUILTIN_TYPE_NULL)]],
            ['bar', [new Type(Type::BUILTIN_TYPE_INT, true)]],
            ['baz', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))]],
            ['donotexist', null],
        ];
    }

    /**
     * @dataProvider php80TypesProvider
     */
    public function testExtractPhp80Type($property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Php80Dummy', $property, []));
    }

    public static function php80TypesProvider()
    {
        return [
            ['foo', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)]],
            ['bar', [new Type(Type::BUILTIN_TYPE_INT, true)]],
            ['timeout', [new Type(Type::BUILTIN_TYPE_FLOAT), new Type(Type::BUILTIN_TYPE_INT)]],
            ['optional', [new Type(Type::BUILTIN_TYPE_FLOAT, true), new Type(Type::BUILTIN_TYPE_INT, true)]],
            ['string', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Stringable'), new Type(Type::BUILTIN_TYPE_STRING)]],
            ['payload', null],
            ['data', null],
            ['mixedProperty', null],
        ];
    }

    /**
     * @dataProvider php81TypesProvider
     */
    public function testExtractPhp81Type($property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Php81Dummy', $property, []));
    }

    public static function php81TypesProvider()
    {
        return [
            ['nothing', null],
            ['collection', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Countable'), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Traversable')]],
        ];
    }

    /**
     * @dataProvider php82TypesProvider
     */
    public function testExtractPhp82Type($property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Php82Dummy', $property, []));
    }

    public static function php82TypesProvider(): iterable
    {
        yield ['nil', null];
        yield ['false', [new Type(Type::BUILTIN_TYPE_FALSE)]];
        yield ['true', [new Type(Type::BUILTIN_TYPE_TRUE)]];

        // Nesting intersection and union types is not supported yet,
        // but we should make sure this kind of composite types does not crash the extractor.
        yield ['someCollection', null];
    }

    /**
     * @dataProvider defaultValueProvider
     */
    public function testExtractWithDefaultValue($property, $type)
    {
        $this->assertEquals($type, $this->extractor->getTypes(DefaultValue::class, $property, []));
    }

    public static function defaultValueProvider()
    {
        return [
            ['defaultInt', [new Type(Type::BUILTIN_TYPE_INT, false)]],
            ['defaultFloat', [new Type(Type::BUILTIN_TYPE_FLOAT, false)]],
            ['defaultString', [new Type(Type::BUILTIN_TYPE_STRING, false)]],
            ['defaultArray', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true)]],
            ['defaultNull', null],
        ];
    }

    /**
     * @dataProvider constructorTypesProvider
     */
    public function testExtractTypeConstructor(string $class, string $property, array $type = null)
    {
        /* Check that constructor extractions works by default, and if passed in via context.
           Check that null is returned if constructor extraction is disabled */
        $this->assertEquals($type, $this->extractor->getTypes($class, $property, []));
        $this->assertEquals($type, $this->extractor->getTypes($class, $property, ['enable_constructor_extraction' => true]));
        $this->assertNull($this->extractor->getTypes($class, $property, ['enable_constructor_extraction' => false]));
    }

    public static function constructorTypesProvider(): array
    {
        return [
            // php71 dummy has following constructor: __construct(string $string, int $intPrivate)
            [Php71Dummy::class, 'string', [new Type(Type::BUILTIN_TYPE_STRING, false)]],
            [Php71Dummy::class, 'intPrivate', [new Type(Type::BUILTIN_TYPE_INT, false)]],
            // Php71DummyExtended2 adds int $intWithAccessor
            [Php71DummyExtended2::class, 'intWithAccessor', [new Type(Type::BUILTIN_TYPE_INT, false)]],
            [Php71DummyExtended2::class, 'intPrivate', [new Type(Type::BUILTIN_TYPE_INT, false)]],
            [DefaultValue::class, 'foo', null],
        ];
    }

    public function testTypedProperties()
    {
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)], $this->extractor->getTypes(Php74Dummy::class, 'dummy'));
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_BOOL, true)], $this->extractor->getTypes(Php74Dummy::class, 'nullableBoolProp'));
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))], $this->extractor->getTypes(Php74Dummy::class, 'stringCollection'));
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_INT, true)], $this->extractor->getTypes(Php74Dummy::class, 'nullableWithDefault'));
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true)], $this->extractor->getTypes(Php74Dummy::class, 'collection'));
    }

    /**
     * @dataProvider extractConstructorTypesProvider
     */
    public function testExtractConstructorTypes(string $property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypesFromConstructor('Symfony\Component\PropertyInfo\Tests\Fixtures\ConstructorDummy', $property));
    }

    public static function extractConstructorTypesProvider(): array
    {
        return [
            ['timezone', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeZone')]],
            ['date', null],
            ['dateObject', null],
            ['dateTime', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable')]],
            ['ddd', null],
        ];
    }
}
