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

use Symfony\Component\TypeInfo\Type as BaseType;

/**
 * Type value object (immutable).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final
 */
class Type
{
    public const BUILTIN_TYPE_INT = BaseType::BUILTIN_TYPE_INT;
    public const BUILTIN_TYPE_FLOAT = BaseType::BUILTIN_TYPE_FLOAT;
    public const BUILTIN_TYPE_STRING = BaseType::BUILTIN_TYPE_STRING;
    public const BUILTIN_TYPE_BOOL = BaseType::BUILTIN_TYPE_BOOL;
    public const BUILTIN_TYPE_RESOURCE = BaseType::BUILTIN_TYPE_RESOURCE;
    public const BUILTIN_TYPE_OBJECT = BaseType::BUILTIN_TYPE_OBJECT;
    public const BUILTIN_TYPE_ARRAY = BaseType::BUILTIN_TYPE_ARRAY;
    public const BUILTIN_TYPE_NULL = BaseType::BUILTIN_TYPE_NULL;
    public const BUILTIN_TYPE_FALSE = BaseType::BUILTIN_TYPE_FALSE;
    public const BUILTIN_TYPE_TRUE = BaseType::BUILTIN_TYPE_TRUE;
    public const BUILTIN_TYPE_CALLABLE = BaseType::BUILTIN_TYPE_CALLABLE;
    public const BUILTIN_TYPE_ITERABLE = BaseType::BUILTIN_TYPE_ITERABLE;

    /**
     * List of PHP builtin types.
     *
     * @var string[]
     */
    public static array $builtinTypes = [
        self::BUILTIN_TYPE_INT,
        self::BUILTIN_TYPE_FLOAT,
        self::BUILTIN_TYPE_STRING,
        self::BUILTIN_TYPE_BOOL,
        self::BUILTIN_TYPE_RESOURCE,
        self::BUILTIN_TYPE_OBJECT,
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_CALLABLE,
        self::BUILTIN_TYPE_FALSE,
        self::BUILTIN_TYPE_TRUE,
        self::BUILTIN_TYPE_NULL,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    /**
     * List of PHP builtin collection types.
     *
     * @var string[]
     */
    public static array $builtinCollectionTypes = [
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    private BaseType $internalType;

    /**
     * @param Type[]|Type|null $collectionKeyType
     * @param Type[]|Type|null $collectionValueType
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $builtinType, bool $nullable = false, string $class = null, bool $collection = false, array|Type $collectionKeyType = null, array|Type $collectionValueType = null)
    {
        if (null !== $collectionKeyType && !is_array($collectionKeyType)) {
            $collectionKeyType = [$collectionKeyType];
        }
        if (null !== $collectionValueType && !is_array($collectionValueType)) {
            $collectionValueType = [$collectionValueType];
        }

        $genericParameterTypes = [];
        if (\count($collectionValueType ?? []) > 0) {
            if (\count($collectionKeyType ?? []) > 0) {
                $genericParameterTypes[] = $collectionKeyType;
            }

            $genericParameterTypes[] = $collectionValueType;
        }

        $unionTypes = [];
        if ($nullable) {
            $builtinType = null;
            $unionTypes = [$builtinType, self::BUILTIN_TYPE_NULL];
        }

        $this->internalType = new BaseType($builtinType, $class, $genericParameterTypes, $unionTypes);
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
        return $this->internalType->getBuiltinType();
    }

    /**
     * Gets the class name.
     *
     * Only applicable if the built-in type is object.
     */
    public function getClassName(): ?string
    {
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
        return $this->internalType->getCollectionKeyTypes();
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
        return $this->internalType->getCollectionValueTypes();
    }
}
