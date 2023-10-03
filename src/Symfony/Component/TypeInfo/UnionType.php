<?php

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\LogicException;

final readonly class UnionType extends Type
{
    use CompositeTypeTrait;

    /**
     * @var list<Type>
     */
    private array $types;

    private string $stringRepresentation;

    public function __construct(Type ...$types)
    {
        $this->types = $types;

        $stringRepresentation = '';
        $glue = '';
        foreach ($this->types as $t) {
            $stringRepresentation .= $glue.((string) $t);
            $glue = '|';
        }

        $this->stringRepresentation = $stringRepresentation;
    }

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getBuiltinType(): string
    {
        throw $this->createUnhandledException(__METHOD__);
    }

    public function isNullable(): bool
    {
        return $this->atLeastOneTypeIs(fn (Type $t): bool => $t->isNullable());
    }

    public function isScalar(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isScalar());
    }

    public function isObject(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isObject());
    }

    public function getClassName(): string
    {
        throw $this->createUnhandledException(__METHOD__);
    }

    public function isEnum(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isEnum());
    }

    public function isBackedEnum(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isBackedEnum());
    }

    public function getEnumBackingType(): self
    {
        throw $this->createUnhandledException(__METHOD__);
    }

    public function isCollection(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isCollection());
    }

    public function getCollectionKeyType(): self
    {
        throw $this->createUnhandledException(__METHOD__);
    }

    public function getCollectionValueType(): self
    {
        throw $this->createUnhandledException(__METHOD__);
    }

    public function isList(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isList());
    }

    public function isDict(): bool
    {
        return $this->everyTypeIs(fn (Type $t): bool => $t->isDict());
    }

    public function __toString(): string
    {
        return $this->stringRepresentation;
    }
}
