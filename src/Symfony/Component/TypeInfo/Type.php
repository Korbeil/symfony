<?php

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;

class Type
{
    public const BUILTIN_TYPE_INT = 'int';
    public const BUILTIN_TYPE_FLOAT = 'float';
    public const BUILTIN_TYPE_STRING = 'string';
    public const BUILTIN_TYPE_BOOL = 'bool';
    public const BUILTIN_TYPE_RESOURCE = 'resource';
    public const BUILTIN_TYPE_OBJECT = 'object';
    public const BUILTIN_TYPE_ARRAY = 'array';
    public const BUILTIN_TYPE_MIXED = 'mixed';
    public const BUILTIN_TYPE_NULL = 'null';
    public const BUILTIN_TYPE_FALSE = 'false';
    public const BUILTIN_TYPE_TRUE = 'true';
    public const BUILTIN_TYPE_CALLABLE = 'callable';
    public const BUILTIN_TYPE_ITERABLE = 'iterable';

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
        self::BUILTIN_TYPE_MIXED,
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

    private bool $collection = false;
    private bool $enum = false;

    public function __construct(
        private ?string $builtinType = null,
        private readonly ?string $className = null,
        private readonly array $genericParameterTypes = [],
        private readonly array $unionTypes = [],
        private readonly array $intersectionTypes = [],
        private readonly ?self $enumBackingType = null,
    ) {
        if (!\in_array($builtinType, self::$builtinTypes)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid PHP type.', $builtinType));
        }

        if (1 === \count($this->unionTypes)) {
            throw new InvalidArgumentException(sprintf('Cannot define only one union type for "%s" type.', $this->builtinType));
        }

        if (1 === \count($this->intersectionTypes)) {
            throw new InvalidArgumentException(sprintf('Cannot define only one intersection type for "%s" type.', $this->builtinType));
        }

        if (null !== $this->className) {
            $this->builtinType = self::BUILTIN_TYPE_OBJECT;
        }

        if (in_array($this->builtinType, self::$builtinCollectionTypes)) {
            $this->collection = true;
        }

        if (is_subclass_of($className, \UnitEnum::class)) {
            $this->enum = true;
        }
    }

    /**
     * Gets built-in type.
     *
     * Can be bool, int, float, string, array, object, resource, null, callback or iterable.
     */
    public function getBuiltinType(): string
    {
        return $this->builtinType;
    }

    public function isNullable(): bool
    {
        return self::BUILTIN_TYPE_NULL === $this->builtinType || (\count($this->unionTypes) > 0 && in_array(self::BUILTIN_TYPE_NULL, $this->unionTypes));
    }

    /**
     * Gets the class name.
     *
     * Only applicable if the built-in type is object.
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function isEnum(): bool
    {
        return $this->enum;
    }

    public function getEnumBackingType(): ?self
    {
        return $this->enumBackingType;
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
        if (!$this->collection) {
            return [];
        }

        if (\count($this->genericParameterTypes) > 1) {
            return $this->genericParameterTypes[0];
        }

        return [new self(self::BUILTIN_TYPE_INT)];
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
        if (!$this->collection) {
            return [];
        }

        $genericParameterTypesCount = \count($this->genericParameterTypes);

        return match ($genericParameterTypesCount) {
            2 => [$this->genericParameterTypes[1]],
            1 => [$this->genericParameterTypes[0]],
            default => [new self(self::BUILTIN_TYPE_MIXED)],
        };
    }
}
