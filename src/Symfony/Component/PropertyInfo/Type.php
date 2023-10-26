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

use Symfony\Component\PropertyInfo\Util\BackwardCompatibilityHelper;
use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Type value object (immutable).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @final
 */
class Type
{
    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_INT = 'int';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_FLOAT = 'float';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_STRING = 'string';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_BOOL = 'bool';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_RESOURCE = 'resource';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_OBJECT = 'object';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_ARRAY = 'array';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_NULL = 'null';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_FALSE = 'false';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_TRUE = 'true';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_CALLABLE = 'callable';

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public const BUILTIN_TYPE_ITERABLE = 'iterable';

    /**
     * List of PHP builtin types.
     *
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
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
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     *
     * @var string[]
     */
    public static array $builtinCollectionTypes = [
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    /**
     * @internal
     */
    public TypeInfoType $internalType;

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     *
     * @param Type[]|Type|null $collectionKeyType
     * @param Type[]|Type|null $collectionValueType
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $builtinType, bool $nullable = false, string $class = null, bool $collection = false, array|self $collectionKeyType = null, array|self $collectionValueType = null)
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        $genericTypes = [];

        $collectionKeyType = $this->validateCollectionArgument($collectionKeyType, 5, '$collectionKeyType') ?? [];
        $collectionValueType = $this->validateCollectionArgument($collectionValueType, 6, '$collectionValueType') ?? [];

        if ($collectionKeyType) {
            if (\is_array($collectionKeyType)) {
                $collectionKeyType = array_unique(array_map(fn ($t): TypeInfoType => $t->internalType, $collectionKeyType));
                $genericTypes[] = \count($collectionKeyType) > 1 ? TypeInfoType::union(...$collectionKeyType) : $collectionKeyType[0];
            } else {
                $genericTypes[] = $collectionKeyType->internalType;
            }
        }

        if ($collectionValueType) {
            if (!$collectionKeyType) {
                $genericTypes[] = [] === $collectionKeyType ? TypeInfoType::mixed() : TypeInfoType::union(TypeInfoType::int(), TypeInfoType::string());
            }

            if (\is_array($collectionValueType)) {
                $collectionValueType = array_unique(array_map(fn ($t): TypeInfoType => $t->internalType, $collectionValueType));
                $genericTypes[] = \count($collectionValueType) > 1 ? TypeInfoType::union(...$collectionValueType) : $collectionValueType[0];
            } else {
                $genericTypes[] = $collectionValueType->internalType;
            }
        }

        if ($collectionKeyType && !$collectionValueType) {
            $genericTypes[] = TypeInfoType::mixed();
        }

        try {
            $this->internalType = null !== $class ? new ObjectType($class) : new BuiltinType(BuiltinTypeEnum::from($builtinType));
        } catch (\ValueError) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid PHP type.', $builtinType));
        }

        if (\count($genericTypes)) {
            $this->internalType = TypeInfoType::generic($this->internalType, ...$genericTypes);
        }

        if (\in_array($builtinType, ['mixed', 'null'], true)) {
            $nullable = true;
        }

        if ($nullable && !$this->internalType->isNullable) {
            $this->internalType = TypeInfoType::nullable($this->internalType);
        }

        $this->internalType->setCollection($collection);
        $this->internalType->setNullable($nullable);
    }

    /**
     * Gets built-in type.
     *
     * Can be bool, int, float, string, array, object, resource, null, callback or iterable.
     *
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public function getBuiltinType(): string
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);
        $internalType = $internalType->getBaseType();

        return $internalType instanceof BuiltinType ? $internalType->getBuiltinType()->value : 'object';
    }

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public function isNullable(): bool
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        return $this->internalType->isNullable;
    }

    /**
     * Gets the class name.
     *
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     *
     * Only applicable if the built-in type is object.
     */
    public function getClassName(): ?string
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);
        $internalType = $internalType->getBaseType();

        if (!$internalType instanceof ObjectType) {
            return null;
        }

        return $internalType->getClassName();
    }

    /**
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     */
    public function isCollection(): bool
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        return $this->internalType->isCollection;
    }

    /**
     * Gets collection key types.
     *
     * Only applicable for a collection type.
     *
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     *
     * @return Type[]
     */
    public function getCollectionKeyTypes(): array
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);

        if (!$internalType instanceof GenericType) {
            return [];
        }

        if (null === ($collectionKeyType = $internalType->getGenericTypes()[0] ?? null)) {
            return [];
        }

        return BackwardCompatibilityHelper::convertTypeToLegacyTypes($collectionKeyType);
    }

    /**
     * Gets collection value types.
     *
     * Only applicable for a collection type.
     *
     * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
     *
     * @return Type[]
     */
    public function getCollectionValueTypes(): array
    {
        trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', self::class, TypeInfoType::class);

        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);

        if (!$internalType instanceof GenericType) {
            return [];
        }

        if (null === ($collectionValueType = $internalType->getGenericTypes()[1] ?? null)) {
            return [];
        }

        return BackwardCompatibilityHelper::convertTypeToLegacyTypes($collectionValueType);
    }

    private function validateCollectionArgument(array|self|null $collectionArgument, int $argumentIndex, string $argumentName): ?array
    {
        if (null === $collectionArgument) {
            return null;
        }

        if (\is_array($collectionArgument)) {
            foreach ($collectionArgument as $type) {
                if (!$type instanceof self) {
                    throw new \TypeError(sprintf('"%s()": Argument #%d (%s) must be of type "%s[]", "%s" or "null", array value "%s" given.', __METHOD__, $argumentIndex, $argumentName, self::class, self::class, get_debug_type($collectionArgument)));
                }
            }

            return $collectionArgument;
        }

        return [$collectionArgument];
    }
}
