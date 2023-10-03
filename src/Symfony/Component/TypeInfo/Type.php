<?php

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Exception\LogicException;

readonly class Type implements \Stringable
{
    use TypeFactoryTrait;

    public const BUILTIN_TYPE_INT = 'int';
    public const BUILTIN_TYPE_FLOAT = 'float';
    public const BUILTIN_TYPE_STRING = 'string';
    public const BUILTIN_TYPE_BOOL = 'bool';
    public const BUILTIN_TYPE_RESOURCE = 'resource';
    public const BUILTIN_TYPE_OBJECT = 'object';
    public const BUILTIN_TYPE_ARRAY = 'array';
    public const BUILTIN_TYPE_NULL = 'null';
    public const BUILTIN_TYPE_FALSE = 'false';
    public const BUILTIN_TYPE_TRUE = 'true';
    public const BUILTIN_TYPE_CALLABLE = 'callable';
    public const BUILTIN_TYPE_ITERABLE = 'iterable';
    public const BUILTIN_TYPE_MIXED = 'mixed';

    public const BUILTIN_TYPES = [
        self::BUILTIN_TYPE_INT,
        self::BUILTIN_TYPE_FLOAT,
        self::BUILTIN_TYPE_STRING,
        self::BUILTIN_TYPE_BOOL,
        self::BUILTIN_TYPE_RESOURCE,
        self::BUILTIN_TYPE_OBJECT,
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_NULL,
        self::BUILTIN_TYPE_FALSE,
        self::BUILTIN_TYPE_TRUE,
        self::BUILTIN_TYPE_CALLABLE,
        self::BUILTIN_TYPE_ITERABLE,
        self::BUILTIN_TYPE_MIXED,
    ];

    private string $builtinType;

    /**
     * @var class-string|null
     */
    private ?string $className;

    private ?self $enumBackingType;

    private bool $isCollection;

    private bool $isEnum;

    private bool $isBackedEnum;

    public function __construct(
        ?string $builtinType = null,
        ?string $className = null,
        ?self $enumBackingType = null,
        ?bool $isCollection = null,
    ) {
        $isCollection = false;
        $isEnum = $isBackedEnum = false;

        if (null !== $className) {
            $builtinType = self::BUILTIN_TYPE_OBJECT;
        }

        if (!\in_array($builtinType, self::BUILTIN_TYPES)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid PHP type.', $builtinType));
        }

        if (self::BUILTIN_TYPE_OBJECT === $builtinType && null === $className) {
            $className = \stdClass::class;
        }

        if (is_subclass_of($className, \UnitEnum::class)) {
            $isEnum = true;
        }

        if (null !== $enumBackingType) {
            if (!is_subclass_of($className, \BackedEnum::class)) {
                throw new InvalidArgumentException(sprintf('Cannot set an enum backing type as "%s" is not a valid backed enum.', $className));
            }

            $isBackedEnum = true;
        }

        if (null === $isCollection) {
            $isCollection = in_array($builtinType, [self::BUILTIN_TYPE_ARRAY, self::BUILTIN_TYPE_ITERABLE]);
        }

        $this->builtinType = $builtinType;
        $this->className = $className;
        $this->enumBackingType = $enumBackingType;
        $this->isCollection = $isCollection;
        $this->isEnum = $isEnum;
        $this->isBackedEnum = $isBackedEnum;
    }

    public function getBuiltinType(): string
    {
        return $this->builtinType;
    }

    public function isNullable(): bool
    {
        return self::BUILTIN_TYPE_NULL === $this->builtinType;
    }

    public function isScalar(): bool
    {
        return in_array($this->builtinType, [
            self::BUILTIN_TYPE_INT,
            self::BUILTIN_TYPE_FLOAT,
            self::BUILTIN_TYPE_STRING,
            self::BUILTIN_TYPE_BOOL,
            self::BUILTIN_TYPE_NULL,
            self::BUILTIN_TYPE_FALSE,
            self::BUILTIN_TYPE_TRUE,
        ]);
    }

    public function isObject(): bool
    {
        return self::BUILTIN_TYPE_OBJECT === $this->builtinType;
    }

    /**
     * @return class-string
     *
     * @throws LogicException
     */
    public function getClassName(): string
    {
        if (!$this->isObject()) {
            throw new LogicException(sprintf('Cannot get class on "%s" type as it\'s not an object.', (string) $this));
        }

        return $this->className;
    }

    public function isEnum(): bool
    {
        return $this->isEnum;
    }

    public function isBackedEnum(): bool
    {
        return $this->isBackedEnum;
    }

    /**
     * @throws LogicException
     */
    public function getEnumBackingType(): self
    {
        if (!$this->isBackedEnum()) {
            throw new LogicException(sprintf('Cannot get class on "%s" type as it\'s not an enum.', (string) $this));
        }

        return $this->enumBackingType;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * @throws LogicException
     */
    public function getCollectionKeyType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', (string) $this));
        }

        return new UnionType(
            new self(builtinType: self::BUILTIN_TYPE_INT),
            new self(builtinType: self::BUILTIN_TYPE_STRING),
        );
    }

    /**
     * @throws LogicException
     */
    public function getCollectionValueType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', (string) $this));
        }

        return new self(builtinType: self::BUILTIN_TYPE_MIXED);
    }

    public function isList(): bool
    {
        return $this->isCollection() && self::BUILTIN_TYPE_INT === $this->getCollectionKeyType()->getBuiltinType();
    }

    public function isDict(): bool
    {
        return $this->isCollection() && self::BUILTIN_TYPE_STRING === $this->getCollectionKeyType()->getBuiltinType();
    }

    public function __toString(): string
    {
        return $this->builtinType;
    }
}
