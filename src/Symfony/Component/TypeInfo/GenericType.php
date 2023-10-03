<?php

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\LogicException;

final readonly class GenericType extends Type
{
    /**
     * @var list<Type>
     */
    private array $parameterTypes;

    private string $stringRepresentation;

    public function __construct(
        private Type $mainType,
        Type ...$parameterTypes,
    ) {
        parent::__construct($mainType);

        $parameterTypesStringRepresentation = '';
        $glue = '';
        foreach ($parameterTypes as $t) {
            $parameterTypesStringRepresentation .= $glue.((string) $t);
            $glue = ', ';
        }

        $this->stringRepresentation = ((string) $mainType).'<'.$parameterTypesStringRepresentation.'>';
        $this->parameterTypes = $parameterTypes;
    }

    /**
     * @return list<Type>
     */
    public function getParametersType(): array
    {
        return $this->parameterTypes;
    }

    /**
     * @throws LogicException
     */
    public function getCollectionKeyType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', (string) $this));
        }

        return match (\count($this->parameterTypes)) {
            2 => $this->parameterTypes[0],
            1 => new Type(builtinType: self::BUILTIN_TYPE_INT),
            default => parent::getCollectionKeyType(),
        };
    }

    /**
     * @throws LogicException
     */
    public function getCollectionValueType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', (string) $this));
        }

        return match (\count($this->parameterTypes)) {
            2 => $this->parameterTypes[1],
            1 => $this->parameterTypes[0],
            default => parent::getCollectionValueType(),
        };
    }

    public function __toString(): string
    {
        return $this->stringRepresentation;
    }
}
