<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Tests\PropertyInfo;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineDummy;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineEmbeddable;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineEnum;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineGeneratedValue;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineRelation;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineWithEmbedded;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\EnumInt;
use Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\EnumString;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineExtractorTest extends TestCase
{
    private function createExtractor(): DoctrineExtractor
    {
        $config = ORMSetup::createConfiguration(true);
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__.'/../Tests/Fixtures' => 'Symfony\Bridge\Doctrine\Tests\Fixtures'], true));
        $config->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $config->setLazyGhostObjectEnabled(true);

        $eventManager = new EventManager();
        $entityManager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite'], $config, $eventManager), $config, $eventManager);

        if (!DBALType::hasType('foo')) {
            DBALType::addType('foo', 'Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineFooType');
            $entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('custom_foo', 'foo');
        }

        return new DoctrineExtractor($entityManager);
    }

    public function testGetProperties()
    {
        // Fields
        $expected = [
            'id',
            'guid',
            'time',
            'timeImmutable',
            'dateInterval',
            'jsonArray',
            'simpleArray',
            'float',
            'decimal',
            'bool',
            'binary',
            'customFoo',
            'bigint',
            'json',
        ];

        // Associations
        $expected = array_merge($expected, [
            'foo',
            'bar',
            'indexedRguid',
            'indexedBar',
            'indexedFoo',
            'indexedBaz',
            'indexedByDt',
            'indexedByCustomType',
            'indexedBuz',
            'dummyGeneratedValueList',
        ]);

        $this->assertEquals(
            $expected,
            $this->createExtractor()->getProperties(DoctrineDummy::class)
        );
    }

    public function testTestGetPropertiesWithEmbedded()
    {
        $this->assertEquals(
            [
                'id',
                'embedded',
            ],
            $this->createExtractor()->getProperties('Symfony\Bridge\Doctrine\Tests\PropertyInfo\Fixtures\DoctrineWithEmbedded')
        );
    }

    /**
     * @dataProvider typeProvider
     */
    public function testExtract(string $property, ?Type $type)
    {
        $this->assertEquals($type, $this->createExtractor()->getType(DoctrineDummy::class, $property, []));
    }

    public function testExtractWithEmbedded()
    {
        $this->assertEquals(
            Type::object(DoctrineEmbeddable::class),
            $this->createExtractor()->getType(DoctrineWithEmbedded::class, 'embedded'),
        );
    }

    public function testExtractEnum()
    {
        $this->assertEquals(Type::enum(EnumString::class), $this->createExtractor()->getType(DoctrineEnum::class, 'enumString'));
        $this->assertEquals(Type::enum(EnumInt::class), $this->createExtractor()->getType(DoctrineEnum::class, 'enumInt'));
        $this->assertNull($this->createExtractor()->getType(DoctrineEnum::class, 'enumStringArray'));
        $this->assertEquals(Type::list(Type::enum(EnumInt::class)), $this->createExtractor()->getType(DoctrineEnum::class, 'enumIntArray'));
        $this->assertNull($this->createExtractor()->getType(DoctrineEnum::class, 'enumCustom'));
    }

    /**
     * @return iterable<array{0: string, 1: ?Type}>
     */
    public static function typeProvider(): iterable
    {
        yield ['id', Type::int()];
        yield ['guid', Type::string()];
        yield ['bigint', Type::string()];
        yield ['time', Type::object(\DateTime::class)];
        yield ['timeImmutable', Type::object(\DateTimeImmutable::class)];
        yield ['dateInterval', Type::object(\DateInterval::class)];
        yield ['float', Type::float()];
        yield ['decimal', Type::string()];
        yield ['bool', Type::bool()];
        yield ['binary', Type::resource()];
        yield ['jsonArray', Type::array()];
        yield ['foo', Type::nullable(Type::object(DoctrineRelation::class))];
        yield ['bar', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::int())];
        yield ['indexedRguid', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::string())];
        yield ['indexedBar', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::string())];
        yield ['indexedFoo', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::string())];
        yield ['indexedBaz', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::int())];
        yield ['simpleArray', Type::list(Type::string())];
        yield ['customFoo', null];
        yield ['notMapped', null];
        yield ['indexedByDt', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::object())];
        yield ['indexedByCustomType', null];
        yield ['indexedBuz', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::string())];
        yield ['dummyGeneratedValueList', Type::collection(Type::object(Collection::class), Type::object(DoctrineRelation::class), Type::int())];
        yield ['json', null];
    }

    public function testGetPropertiesCatchException()
    {
        $this->assertNull($this->createExtractor()->getProperties('Not\Exist'));
    }

    public function testGetTypeCatchException()
    {
        $this->assertNull($this->createExtractor()->getType('Not\Exist', 'baz'));
    }

    public function testGeneratedValueNotWritable()
    {
        $extractor = $this->createExtractor();
        $this->assertFalse($extractor->isWritable(DoctrineGeneratedValue::class, 'id'));
        $this->assertNull($extractor->isReadable(DoctrineGeneratedValue::class, 'id'));
        $this->assertNull($extractor->isWritable(DoctrineGeneratedValue::class, 'foo'));
        $this->assertNull($extractor->isReadable(DoctrineGeneratedValue::class, 'foo'));
    }
}
