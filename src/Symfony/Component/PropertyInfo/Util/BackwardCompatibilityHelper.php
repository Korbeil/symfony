<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo\Util;

use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final class BackwardCompatibilityHelper
{
    /**
     * Converts a Symfony\Component\TypeInfo\Type what is should have been in the "symfony/property-info" component.
     *
     * @return list<LegacyType>|null
     */
    public static function convertTypeToLegacyTypes(?Type $type, bool $keepNullType = true): ?array
    {
        if (null === $type || 'mixed' === (string) $type) {
            return null;
        }

        if ('null' === (string) $type) {
            return $keepNullType ? [new LegacyType('null')] : null;
        }

        if ('void' === (string) $type) {
            return [new LegacyType('null')];
        }

        try {
            $legacyType = self::convertTypeToLegacy($type);
        } catch (LogicException) {
            return null;
        }

        if (!\is_array($legacyType)) {
            $legacyType = [$legacyType];
        }

        return $legacyType;
    }

    /**
     * Recursive method that converts Symfony\Component\TypeInfo\Type to its related Symfony\Component\PropertyInfo\Type.
     */
    private static function convertTypeToLegacy(Type $type): LegacyType|array
    {
        if ($type instanceof UnionType) {
            $nullable = $type->isNullable();

            $unionTypes = [];
            foreach ($type->getTypes() as $unionType) {
                if ('null' === (string) $unionType) {
                    continue;
                }

                if ($unionType instanceof IntersectionType) {
                    throw new LogicException(sprintf('DNF types are not supported by "%s"', LegacyType::class));
                }

                $unionType->setNullable($nullable);
                $unionTypes[] = $unionType;
            }

            if (1 === \count($unionTypes)) {
                foreach ($type->getTypes() as $intersectionType) {
                    if ($intersectionType instanceof UnionType) {
                        throw new LogicException(sprintf('DNF types are not supported by "%s"', LegacyType::class));
                    }
                }

                return self::convertTypeToLegacy($unionTypes[0]);
            }

            return array_map(self::convertTypeToLegacy(...), $unionTypes);
        }

        if ($type instanceof IntersectionType) {
            return array_map(self::convertTypeToLegacy(...), $type->getTypes());
        }

        if ($type instanceof CollectionType) {
            $nestedType = $type->getType();
            $nestedType->setCollection(true);

            return self::convertTypeToLegacy($nestedType);
        }

        $builtinType = BuiltinTypeEnum::Mixed;
        $className = null;
        $collectionKeyType = $collectionValueType = null;

        if ($type instanceof ObjectType) {
            $builtinType = $type->getBuiltinType();
            $className = $type->getClassName();
        }

        if ($type instanceof GenericType) {
            $nestedType = self::unwrapNullableType($type->getType());

            if ($nestedType instanceof BuiltinType) {
                $builtinType = $nestedType->getBuiltinType();
            } elseif ($nestedType instanceof ObjectType) {
                $builtinType = $nestedType->getBuiltinType();
                $className = $nestedType->getClassName();
            }

            $genericTypes = $type->getGenericTypes();

            if (2 === \count($genericTypes)) {
                $collectionKeyType = self::convertTypeToLegacy($genericTypes[0]);
                $collectionValueType = self::convertTypeToLegacy($genericTypes[1]);
            } elseif (1 === \count($genericTypes)) {
                $collectionValueType = self::convertTypeToLegacy($genericTypes[0]);
            }
        }

        if ($type instanceof BuiltinType) {
            $builtinType = $type->getBuiltinType();
        }

        return new LegacyType(
            builtinType: $builtinType->value,
            nullable: $type->isNullable(),
            class: $className,
            collection: $type instanceof GenericType || $type->isCollection, // legacy generic is always considered as a collection
            collectionKeyType: $collectionKeyType,
            collectionValueType: $collectionValueType,
        );
    }

    public static function unwrapNullableType(Type $type): Type
    {
        if (!$type instanceof UnionType) {
            return $type;
        }

        return $type->asNonNullable();
    }
}
