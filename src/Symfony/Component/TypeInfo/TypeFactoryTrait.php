<?php

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Exception\LogicException;

trait TypeFactoryTrait
{
    public static function int(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_INT);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function float(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_FLOAT);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function string(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_STRING);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function bool(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_BOOL);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function resource(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_RESOURCE);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function object(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function false(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_FALSE);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function true(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_TRUE);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function callable(bool $nullable = false): Type
    {
        $type = new Type(Type::BUILTIN_TYPE_CALLABLE);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function mixed(): Type
    {
        return new Type(Type::BUILTIN_TYPE_MIXED);
    }

    public static function null(): Type
    {
        return new Type(Type::BUILTIN_TYPE_NULL);
    }

    public static function array(Type $value = null, Type $key = null, bool $nullable = false): Type
    {
        $mainType = new Type(Type::BUILTIN_TYPE_ARRAY, $nullable);
        if ($nullable) {
            $mainType = new UnionType(new Type(self::BUILTIN_TYPE_NULL), $mainType);
        }

        if (null === $value && null === $key)  {
            return $mainType;
        }

        return new GenericType(
            $mainType,
            $key ?? self::union(self::int(), self::string()),
            $value ?? self::mixed(),
        );
    }

    public static function list(Type $value = null, bool $nullable = false): Type
    {
        return self::array($value, self::int(), $nullable);
    }

    public static function dict(Type $value = null, bool $nullable = false): Type
    {
        return self::array($value, self::string(), $nullable);
    }

    public static function iterable(bool $nullable = false): Type
    {
        $mainType = new Type(Type::BUILTIN_TYPE_ITERABLE, $nullable);
        if ($nullable) {
            $mainType = new UnionType(new Type(self::BUILTIN_TYPE_NULL), $mainType);
        }

        if (null === $value && null === $key)  {
            return $mainType;
        }

        return new GenericType(
            $mainType,
            $key ?? self::union(self::int(), self::string()),
            $value ?? self::mixed(),
        );
    }

    public static function iterableList(Type $value = null, bool $nullable = false): Type
    {
        return self::iterable($value, self::int(), $nullable);
    }

    public static function iterableDict(Type $value = null, bool $nullable = false): Type
    {
        return self::iterable($value, self::string(), $nullable);
    }

    /**
     * @param class-string $className
     */
    public static function class(string $className, bool $nullable = false): self
    {
        $type = new Type(className: $className);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function enum(string $enumClassName, Type $backingType = null, bool $nullable = false): self
    {
        $type = new Type(className: $className, enumBackingType: $backingType);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function generic(Type $mainType, self ...$parametersType): self
    {
        return new GenericType($mainType, ...$parametersType);
    }

    public static function union(self ...$types): self
    {
        return new UnionType(...$this->types);
    }

    public static function intersection(self ...$types): self
    {
        return new IntersectionType(...$this->types);
    }
}
