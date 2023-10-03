<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo;

use Symfony\Component\TypeInfo\GenericType;
use Symfony\Component\TypeInfo\IntersectionType;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\UnionType;

/**
 * Type value object (immutable).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final
 */
class Type
{
    public const BUILTIN_TYPE_INT = TypeInfoType::BUILTIN_TYPE_INT;
    public const BUILTIN_TYPE_FLOAT = TypeInfoType::BUILTIN_TYPE_FLOAT;
    public const BUILTIN_TYPE_STRING = TypeInfoType::BUILTIN_TYPE_STRING;
    public const BUILTIN_TYPE_BOOL = TypeInfoType::BUILTIN_TYPE_BOOL;
    public const BUILTIN_TYPE_RESOURCE = TypeInfoType::BUILTIN_TYPE_RESOURCE;
    public const BUILTIN_TYPE_OBJECT = TypeInfoType::BUILTIN_TYPE_OBJECT;
    public const BUILTIN_TYPE_ARRAY = TypeInfoType::BUILTIN_TYPE_ARRAY;
    public const BUILTIN_TYPE_NULL = TypeInfoType::BUILTIN_TYPE_NULL;
    public const BUILTIN_TYPE_FALSE = TypeInfoType::BUILTIN_TYPE_FALSE;
    public const BUILTIN_TYPE_TRUE = TypeInfoType::BUILTIN_TYPE_TRUE;
    public const BUILTIN_TYPE_CALLABLE = TypeInfoType::BUILTIN_TYPE_CALLABLE;
    public const BUILTIN_TYPE_ITERABLE = TypeInfoType::BUILTIN_TYPE_ITERABLE;

    /**
     * List of PHP builtin types.
     *
     * @var string[]
     */
    public static array $builtinTypes = TypeInfoType::BUILTIN_TYPES;

    /**
     * List of PHP builtin collection types.
     *
     * @var string[]
     */
    public static array $builtinCollectionTypes = [
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    private TypeInfoType $internalType;

    /**
     * @param Type[]|Type|null $collectionKeyType
     * @param Type[]|Type|null $collectionValueType
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $builtinType, bool $nullable = false, string $class = null, bool $collection = false, array|Type $collectionKeyType = null, array|Type $collectionValueType = null)
    {
        if (null !== $collectionKeyType) {
            $collectionKeyType = (array) $collectionKeyType;
        }
        if (null !== $collectionValueType) {
            $collectionValueType = (array) $collectionValueType;
        }

        $genericParameterTypes = [];
        if (\count($collectionValueType ?? [])) {
            if (\count($collectionKeyType ?? [])) {
                $genericParameterTypes[] = $collectionKeyType->getTypeInfoType();
            }

            $genericParameterTypes[] = $collectionValueType->getTypeInfoType();
        }

        $this->internalType = new TypeInfoType(
            builtinType: $builtinType,
            className: $class,
            isCollection: $collection,
        );

        if (\count($genericParameterTypes)) {
            $this->internalType = new GenericType($this->internalType, ...$genericParameterTypes);
        }

        if ($nullable) {
            $this->internalType = new UnionType(new TypeInfoType(TypeInfoType::BUILTIN_TYPE_NULL), $this->internalType);
        }
    }

    /**
     * Gets built-in type.
     *
     * Can be bool, int, float, string, array, object, resource, null, callback or iterable.
     */
    public function getBuiltinType(): string
    {
        return $this->internalType->getBuiltinType();
    }

    public function isNullable(): bool
    {
        return $this->internalType->isNullable();
    }

    /**
     * Gets the class name.
     *
     * Only applicable if the built-in type is object.
     */
    public function getClassName(): ?string
    {
        if (!$this->internalType->isObject()) {
            return null;
        }

        return $this->internalType->getClassName();
    }

    public function isCollection(): bool
    {
        return $this->internalType->isCollection();
    }

    /**
     * Gets collection key types.
     *
     * Only applicable for a collection type.
     *
     * @return Type[]
     */
    public function getCollectionKeyTypes(): array
    {
        if (!$this->internalType->isCollection()) {
            return [];
        }

        return (array) $this->convertFromTypeInfoType($this->internalType->getCollectionKeyType());
    }

    /**
     * Gets collection value types.
     *
     * Only applicable for a collection type.
     *
     * @return Type[]
     */
    public function getCollectionValueTypes(): array
    {
        if (!$this->internalType->isCollection()) {
            return [];
        }

        return (array) $this->convertFromTypeInfoType($this->internalType->getCollectionValueType());
    }

    private function getTypeInfoType(): TypeInfoType
    {
        return $this->internalType;
    }

    /**
     * @return self|list<self>
     */
    private function convertFromTypeInfoType(TypeInfoType $typeInfoType): self|array
    {
        if ($typeInfoType instanceof UnionType) {
            return array_map($this->convertFromTypeInfoType(...), $typeInfoType->getTypes());
        }

        if ($typeInfoType instanceof IntersectionType) {
            return array_map($this->convertFromTypeInfoType(...), $typeInfoType->getTypes());
        }

        $className = null;
        if ($typeInfoType->isObject()) {
            $className = $typeInfoType->getClassName();
        }

        $collectionKeyType = $collectionValueType = null;
        if ($typeInfoType instanceof GenericType && $typeInfoType->isCollection()) {
            $collectionKeyType = $this->convertFromTypeInfoType($typeInfoType->getCollectionKeyType());
            $collectionValueType = $this->convertFromTypeInfoType($typeInfoType->getCollectionValueType());
        }

        return new self(
            builtinType: $typeInfoType->getBuiltinType(),
            nullable: $typeInfoType->isNullable(),
            class: $className,
            collection: $typeInfoType->isCollection(),
            collectionKeyType: $collectionKeyType,
            collectionValueType: $collectionValueType,
        );
    }
}
