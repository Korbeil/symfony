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
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\Php80Dummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\PseudoTypeDummy;
use Symfony\Component\PropertyInfo\Tests\Fixtures\TraitUsage\DummyUsedInTrait;
use Symfony\Component\PropertyInfo\Tests\Fixtures\TraitUsage\DummyUsingTrait;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class LegacyPhpDocExtractorTest extends TestCase
{
    private PhpDocExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new PhpDocExtractor();
    }

    /**
     * @dataProvider typesProvider
     */
    public function testExtract($property, ?array $type)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    public function testParamTagTypeIsOmitted()
    {
        $this->assertNull($this->extractor->getTypes(LegacyOmittedParamTagTypeDocBlock::class, 'omittedType'));
    }

    public static function invalidTypesProvider()
    {
        return [
            'pub' => ['pub'],
            'stat' => ['stat'],
            'foo' => ['foo'],
            'bar' => ['bar'],
        ];
    }

    /**
     * @dataProvider invalidTypesProvider
     */
    public function testInvalid($property)
    {
        $this->assertNull($this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\InvalidDummy', $property));
    }

    /**
     * @dataProvider typesWithNoPrefixesProvider
     */
    public function testExtractTypesWithNoPrefixes($property, array $type = null)
    {
        $noPrefixExtractor = new PhpDocExtractor(null, [], [], []);

        $this->assertEquals($type, $noPrefixExtractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    public static function typesProvider()
    {
        return [
            ['foo', null],
            ['bar', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['baz', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['foo2', [new Type(Type::BUILTIN_TYPE_FLOAT)]],
            ['foo3', [new Type(Type::BUILTIN_TYPE_CALLABLE)]],
            ['foo4', [new Type(Type::BUILTIN_TYPE_NULL)]],
            ['foo5', null],
            [
                'files',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ],
            ],
            ['bal', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable')]],
            ['parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')]],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable'))]],
            ['nestedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))]],
            ['mixedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)]],
            ['a', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['b', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')]],
            ['c', [new Type(Type::BUILTIN_TYPE_BOOL, true)]],
            ['ct', [new Type(Type::BUILTIN_TYPE_TRUE, true)]],
            ['cf', [new Type(Type::BUILTIN_TYPE_FALSE, true)]],
            ['d', [new Type(Type::BUILTIN_TYPE_BOOL)]],
            ['dt', [new Type(Type::BUILTIN_TYPE_TRUE)]],
            ['df', [new Type(Type::BUILTIN_TYPE_FALSE)]],
            ['e', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_RESOURCE))]],
            ['f', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable'))]],
            ['g', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)]],
            ['h', [new Type(Type::BUILTIN_TYPE_STRING, true)]],
            ['i', [new Type(Type::BUILTIN_TYPE_INT, true), new Type(Type::BUILTIN_TYPE_STRING, true)]],
            ['j', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'DateTimeImmutable')]],
            ['nullableCollectionOfNonNullableElements', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_INT, false))]],
            ['donotexist', null],
            ['staticGetter', null],
            ['staticSetter', null],
            ['emptyVar', null],
            ['arrayWithKeys', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_STRING), new Type(Type::BUILTIN_TYPE_STRING))]],
            ['arrayOfMixed', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_STRING), null)]],
            ['listOfStrings', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))]],
            ['self', [new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)]],
        ];
    }

    /**
     * @dataProvider provideCollectionTypes
     */
    public function testExtractCollection($property, ?array $type)
    {
        $this->testExtract($property, $type);
    }

    public static function provideCollectionTypes()
    {
        return [
            ['iteratorCollection', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Iterator', true, [new Type(Type::BUILTIN_TYPE_STRING), new Type(Type::BUILTIN_TYPE_INT)], new Type(Type::BUILTIN_TYPE_STRING))]],
            ['iteratorCollectionWithKey', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Iterator', true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))]],
            [
                'nestedIterators',
                [new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    'Iterator',
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Iterator', true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))
                )],
            ],
            [
                'arrayWithKeys',
                [new Type(
                    Type::BUILTIN_TYPE_ARRAY,
                    false,
                    null,
                    true,
                    new Type(Type::BUILTIN_TYPE_STRING),
                    new Type(Type::BUILTIN_TYPE_STRING)
                )],
            ],
            [
                'arrayWithKeysAndComplexValue',
                [new Type(
                    Type::BUILTIN_TYPE_ARRAY,
                    false,
                    null,
                    true,
                    new Type(Type::BUILTIN_TYPE_STRING),
                    new Type(
                        Type::BUILTIN_TYPE_ARRAY,
                        true,
                        null,
                        true,
                        new Type(Type::BUILTIN_TYPE_INT),
                        new Type(Type::BUILTIN_TYPE_STRING, true)
                    )
                )],
            ],
        ];
    }

    /**
     * @dataProvider typesWithCustomPrefixesProvider
     */
    public function testExtractTypesWithCustomPrefixes($property, array $type = null)
    {
        $customExtractor = new PhpDocExtractor(null, ['add', 'remove'], ['is', 'can']);

        $this->assertEquals($type, $customExtractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    public static function typesWithCustomPrefixesProvider()
    {
        return [
            ['foo', null],
            ['bar', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['baz', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['foo2', [new Type(Type::BUILTIN_TYPE_FLOAT)]],
            ['foo3', [new Type(Type::BUILTIN_TYPE_CALLABLE)]],
            ['foo4', [new Type(Type::BUILTIN_TYPE_NULL)]],
            ['foo5', null],
            [
                'files',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ],
            ],
            ['bal', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable')]],
            ['parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')]],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable'))]],
            ['nestedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))]],
            ['mixedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)]],
            ['a', null],
            ['b', null],
            ['c', [new Type(Type::BUILTIN_TYPE_BOOL, true)]],
            ['d', [new Type(Type::BUILTIN_TYPE_BOOL)]],
            ['e', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_RESOURCE))]],
            ['f', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable'))]],
            ['g', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)]],
            ['h', [new Type(Type::BUILTIN_TYPE_STRING, true)]],
            ['i', [new Type(Type::BUILTIN_TYPE_INT, true), new Type(Type::BUILTIN_TYPE_STRING, true)]],
            ['j', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'DateTimeImmutable')]],
            ['nullableCollectionOfNonNullableElements', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_INT, false))]],
            ['nonNullableCollectionOfNullableElements', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_INT, true))]],
            ['nullableCollectionOfMultipleNonNullableElementTypes', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true, new Type(Type::BUILTIN_TYPE_INT), [new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING)])]],
            ['donotexist', null],
            ['staticGetter', null],
            ['staticSetter', null],
        ];
    }

    public static function typesWithNoPrefixesProvider()
    {
        return [
            ['foo', null],
            ['bar', [new Type(Type::BUILTIN_TYPE_STRING)]],
            ['baz', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['foo2', [new Type(Type::BUILTIN_TYPE_FLOAT)]],
            ['foo3', [new Type(Type::BUILTIN_TYPE_CALLABLE)]],
            ['foo4', [new Type(Type::BUILTIN_TYPE_NULL)]],
            ['foo5', null],
            [
                'files',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ],
            ],
            ['bal', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable')]],
            ['parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')]],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeImmutable'))]],
            ['nestedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))]],
            ['mixedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)]],
            ['a', null],
            ['b', null],
            ['c', null],
            ['d', null],
            ['e', null],
            ['f', null],
            ['g', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)]],
            ['h', [new Type(Type::BUILTIN_TYPE_STRING, true)]],
            ['i', [new Type(Type::BUILTIN_TYPE_INT, true), new Type(Type::BUILTIN_TYPE_STRING, true)]],
            ['j', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'DateTimeImmutable')]],
            ['nullableCollectionOfNonNullableElements', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_INT, false))]],
            ['donotexist', null],
            ['staticGetter', null],
            ['staticSetter', null],
        ];
    }

    public static function dockBlockFallbackTypesProvider()
    {
        return [
            'pub' => [
                'pub', [new Type(Type::BUILTIN_TYPE_STRING)],
            ],
            'protAcc' => [
                'protAcc', [new Type(Type::BUILTIN_TYPE_INT)],
            ],
            'protMut' => [
                'protMut', [new Type(Type::BUILTIN_TYPE_BOOL)],
            ],
        ];
    }

    /**
     * @dataProvider dockBlockFallbackTypesProvider
     */
    public function testDocBlockFallback($property, $types)
    {
        $this->assertEquals($types, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\DockBlockFallback', $property));
    }

    /**
     * @dataProvider propertiesDefinedByTraitsProvider
     */
    public function testPropertiesDefinedByTraits(string $property, Type $type)
    {
        $this->assertEquals([$type], $this->extractor->getTypes(DummyUsingTrait::class, $property));
    }

    public static function propertiesDefinedByTraitsProvider(): array
    {
        return [
            ['propertyInTraitPrimitiveType', new Type(Type::BUILTIN_TYPE_STRING)],
            ['propertyInTraitObjectSameNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, DummyUsedInTrait::class)],
            ['propertyInTraitObjectDifferentNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)],
            ['propertyInExternalTraitPrimitiveType', new Type(Type::BUILTIN_TYPE_STRING)],
            ['propertyInExternalTraitObjectSameNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)],
            ['propertyInExternalTraitObjectDifferentNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, DummyUsedInTrait::class)],
        ];
    }

    /**
     * @dataProvider methodsDefinedByTraitsProvider
     */
    public function testMethodsDefinedByTraits(string $property, Type $type)
    {
        $this->assertEquals([$type], $this->extractor->getTypes(DummyUsingTrait::class, $property));
    }

    public static function methodsDefinedByTraitsProvider(): array
    {
        return [
            ['methodInTraitPrimitiveType', new Type(Type::BUILTIN_TYPE_STRING)],
            ['methodInTraitObjectSameNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, DummyUsedInTrait::class)],
            ['methodInTraitObjectDifferentNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)],
            ['methodInExternalTraitPrimitiveType', new Type(Type::BUILTIN_TYPE_STRING)],
            ['methodInExternalTraitObjectSameNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)],
            ['methodInExternalTraitObjectDifferentNamespace', new Type(Type::BUILTIN_TYPE_OBJECT, false, DummyUsedInTrait::class)],
        ];
    }

    /**
     * @dataProvider propertiesStaticTypeProvider
     */
    public function testPropertiesStaticType(string $class, string $property, Type $type)
    {
        $this->assertEquals([$type], $this->extractor->getTypes($class, $property));
    }

    public static function propertiesStaticTypeProvider(): array
    {
        return [
            [ParentDummy::class, 'propertyTypeStatic', new Type(Type::BUILTIN_TYPE_OBJECT, false, ParentDummy::class)],
            [Dummy::class, 'propertyTypeStatic', new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)],
        ];
    }

    /**
     * @dataProvider propertiesParentTypeProvider
     */
    public function testPropertiesParentType(string $class, string $property, ?array $types)
    {
        $this->assertEquals($types, $this->extractor->getTypes($class, $property));
    }

    public static function propertiesParentTypeProvider(): array
    {
        return [
            [ParentDummy::class, 'parentAnnotationNoParent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'parent')]],
            [Dummy::class, 'parentAnnotation', [new Type(Type::BUILTIN_TYPE_OBJECT, false, ParentDummy::class)]],
        ];
    }

    public function testUnknownPseudoType()
    {
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_OBJECT, false, 'scalar')], $this->extractor->getTypes(PseudoTypeDummy::class, 'unknownPseudoType'));
    }

    /**
     * @dataProvider constructorTypesProvider
     */
    public function testExtractConstructorTypes($property, array $type = null)
    {
        $this->assertEquals($type, $this->extractor->getTypesFromConstructor('Symfony\Component\PropertyInfo\Tests\Fixtures\ConstructorDummy', $property));
    }

    public static function constructorTypesProvider()
    {
        return [
            ['date', [new Type(Type::BUILTIN_TYPE_INT)]],
            ['timezone', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeZone')]],
            ['dateObject', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTimeInterface')]],
            ['dateTime', null],
            ['ddd', null],
            ['mixed', null],
        ];
    }

    /**
     * @dataProvider pseudoTypesProvider
     */
    public function testPseudoTypes($property, array $type)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\PseudoTypesDummy', $property));
    }

    public static function pseudoTypesProvider(): array
    {
        return [
            ['classString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['classStringGeneric', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['htmlEscapedString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['lowercaseString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['nonEmptyLowercaseString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['nonEmptyString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['numericString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['traitString', [new Type(Type::BUILTIN_TYPE_STRING, false, null)]],
            ['positiveInt', [new Type(Type::BUILTIN_TYPE_INT, false, null)]],
        ];
    }

    /**
     * @dataProvider promotedPropertyProvider
     */
    public function testExtractPromotedProperty(string $property, ?array $types)
    {
        $this->assertEquals($types, $this->extractor->getTypes(Php80Dummy::class, $property));
    }

    public static function promotedPropertyProvider(): array
    {
        return [
            ['promoted', null],
            ['promotedAndMutated', [new Type(Type::BUILTIN_TYPE_STRING)]],
        ];
    }
}

class LegacyOmittedParamTagTypeDocBlock
{
    /**
     * The type is omitted here to ensure that the extractor doesn't choke on missing types.
     */
    public function setOmittedType(array $omittedTagType)
    {
    }
}
