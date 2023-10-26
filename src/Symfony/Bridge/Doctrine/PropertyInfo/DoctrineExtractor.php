<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\PropertyInfo;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException as OrmMappingException;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Util\BackwardCompatibilityHelper;
use Symfony\Component\TypeInfo\Type;

/**
 * Extracts data using Doctrine ORM and ODM metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DoctrineExtractor implements PropertyListExtractorInterface, PropertyTypeExtractorInterface, PropertyAccessExtractorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getProperties(string $class, array $context = []): ?array
    {
        if (null === $metadata = $this->getMetadata($class)) {
            return null;
        }

        $properties = array_merge($metadata->getFieldNames(), $metadata->getAssociationNames());

        if ($metadata instanceof ClassMetadata && $metadata->embeddedClasses) {
            $properties = array_filter($properties, fn ($property) => !str_contains($property, '.'));

            $properties = array_merge($properties, array_keys($metadata->embeddedClasses));
        }

        return $properties;
    }

    public function getType(string $class, string $property, array $context = []): ?Type
    {
        if (null === $metadata = $this->getMetadata($class)) {
            return null;
        }

        if ($metadata->hasAssociation($property)) {
            $class = $metadata->getAssociationTargetClass($property);

            if ($metadata->isSingleValuedAssociation($property)) {
                if ($metadata instanceof ClassMetadata) {
                    $associationMapping = $metadata->getAssociationMapping($property);
                    $nullable = $this->isAssociationNullable($associationMapping);
                } else {
                    $nullable = false;
                }

                $t = Type::object($class);

                return $nullable ? Type::nullable($t) : $t;
            }

            $collectionKeyType = 'int';

            if ($metadata instanceof ClassMetadata) {
                $associationMapping = $metadata->getAssociationMapping($property);

                if (isset($associationMapping['indexBy'])) {
                    $subMetadata = $this->entityManager->getClassMetadata($associationMapping['targetEntity']);

                    // Check if indexBy value is a property
                    $fieldName = $associationMapping['indexBy'];
                    if (null === ($typeOfField = $subMetadata->getTypeOfField($fieldName))) {
                        $fieldName = $subMetadata->getFieldForColumn($associationMapping['indexBy']);
                        // Not a property, maybe a column name?
                        if (null === ($typeOfField = $subMetadata->getTypeOfField($fieldName))) {
                            // Maybe the column name is the association join column?
                            $associationMapping = $subMetadata->getAssociationMapping($fieldName);

                            $indexProperty = $subMetadata->getSingleAssociationReferencedJoinColumnName($fieldName);
                            $subMetadata = $this->entityManager->getClassMetadata($associationMapping['targetEntity']);

                            // Not a property, maybe a column name?
                            if (null === ($typeOfField = $subMetadata->getTypeOfField($indexProperty))) {
                                $fieldName = $subMetadata->getFieldForColumn($indexProperty);
                                $typeOfField = $subMetadata->getTypeOfField($fieldName);
                            }
                        }
                    }

                    if (!$collectionKeyType = $this->getPhpType($typeOfField)) {
                        return null;
                    }
                }
            }

            return Type::collection(Type::object(Collection::class), Type::object($class), Type::builtin($collectionKeyType));
        }

        if ($metadata instanceof ClassMetadata && isset($metadata->embeddedClasses[$property])) {
            return Type::object($metadata->embeddedClasses[$property]['class']);
        }

        if ($metadata->hasField($property)) {
            $typeOfField = $metadata->getTypeOfField($property);

            if (!$builtinType = $this->getPhpType($typeOfField)) {
                return null;
            }

            $nullable = $metadata instanceof ClassMetadata && $metadata->isNullable($property);
            $enumType = null;
            if (null !== $enumClass = $metadata->getFieldMapping($property)['enumType'] ?? null) {
                $enumType = Type::enum($enumClass);

                $nullable ? Type::nullable($enumClass) : $enumType;
            }

            switch ($builtinType) {
                case 'object':
                    switch ($typeOfField) {
                        case Types::DATE_MUTABLE:
                        case Types::DATETIME_MUTABLE:
                        case Types::DATETIMETZ_MUTABLE:
                        case 'vardatetime':
                        case Types::TIME_MUTABLE:
                            $t = Type::object(\DateTime::class);

                            return $nullable ? Type::nullable($t) : $t;

                        case Types::DATE_IMMUTABLE:
                        case Types::DATETIME_IMMUTABLE:
                        case Types::DATETIMETZ_IMMUTABLE:
                        case Types::TIME_IMMUTABLE:
                            $t = Type::object(\DateTimeImmutable::class);

                            return $nullable ? Type::nullable($t) : $t;

                        case Types::DATEINTERVAL:
                            $t = Type::object(\DateInterval::class);

                            return $nullable ? Type::nullable($t) : $t;
                    }

                    break;
                case 'array':
                    switch ($typeOfField) {
                        case 'array':      // DBAL < 4
                        case 'json_array': // DBAL < 3
                            // return null if $enumType is set, because we can't determine if collectionKeyType is string or int
                            if ($enumType) {
                                return null;
                            }

                            $t = Type::array();

                            return $nullable ? Type::nullable($t) : $t;

                        case Types::SIMPLE_ARRAY:
                            $t = Type::list($enumType ?? Type::string());

                            return $nullable ? Type::nullable($t) : $t;
                    }
                    break;
                case 'int':
                case 'string':
                    if ($enumType) {
                        return $enumType;
                    }
                    break;
            }

            $t = Type::builtin($builtinType);

            return $nullable ? Type::nullable($t) : $t;
        }

        return null;
    }

    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        trigger_deprecation('symfony/doctrine-bridge', '7.1', 'The "%s()" method is deprecated. Use "%s::getType()" instead.', __METHOD__, self::class);

        return BackwardCompatibilityHelper::convertTypeToLegacyTypes($this->getType($class, $property, $context, true));
    }

    public function isReadable(string $class, string $property, array $context = []): ?bool
    {
        return null;
    }

    public function isWritable(string $class, string $property, array $context = []): ?bool
    {
        if (
            null === ($metadata = $this->getMetadata($class))
            || ClassMetadata::GENERATOR_TYPE_NONE === $metadata->generatorType
            || !\in_array($property, $metadata->getIdentifierFieldNames(), true)
        ) {
            return null;
        }

        return false;
    }

    private function getMetadata(string $class): ?ClassMetadata
    {
        try {
            return $this->entityManager->getClassMetadata($class);
        } catch (MappingException|OrmMappingException) {
            return null;
        }
    }

    /**
     * Determines whether an association is nullable.
     *
     * @param array<string, mixed>|AssociationMapping $associationMapping
     *
     * @see https://github.com/doctrine/doctrine2/blob/v2.5.4/lib/Doctrine/ORM/Tools/EntityGenerator.php#L1221-L1246
     */
    private function isAssociationNullable(array|AssociationMapping $associationMapping): bool
    {
        if (isset($associationMapping['id']) && $associationMapping['id']) {
            return false;
        }

        if (!isset($associationMapping['joinColumns'])) {
            return true;
        }

        $joinColumns = $associationMapping['joinColumns'];
        foreach ($joinColumns as $joinColumn) {
            if (isset($joinColumn['nullable']) && !$joinColumn['nullable']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the corresponding built-in PHP type.
     */
    private function getPhpType(string $doctrineType): ?string
    {
        return match ($doctrineType) {
            Types::SMALLINT,
            Types::INTEGER => 'int',
            Types::FLOAT => 'float',
            Types::BIGINT,
            Types::STRING,
            Types::TEXT,
            Types::GUID,
            Types::DECIMAL => 'string',
            Types::BOOLEAN => 'bool',
            Types::BLOB,
            Types::BINARY => 'resource',
            'object', // DBAL < 4
            Types::DATE_MUTABLE,
            Types::DATETIME_MUTABLE,
            Types::DATETIMETZ_MUTABLE,
            'vardatetime',
            Types::TIME_MUTABLE,
            Types::DATE_IMMUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_IMMUTABLE,
            Types::TIME_IMMUTABLE,
            Types::DATEINTERVAL => 'object',
            'array', // DBAL < 4
            'json_array', // DBAL < 3
            Types::SIMPLE_ARRAY => 'array',
            default => null,
        };
    }
}
