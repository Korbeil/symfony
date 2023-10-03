<?php

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\LogicException;

/**
 * @internal
 */
trait CompositeTypeTrait
{
    /**
     * @param callable(Type): bool $callable
     */
    private function atLeastOneTypeIs(callable $callable): bool
    {
        return count(array_filter($callable, $this->types));
    }

    /**
     * @param callable(Type): bool $callable
     */
    private function everyTypeIs(callable $callable): bool
    {
        foreach ($this->types as $t) {
            if (false === $callable($t)) {
                return false;
            }
        }

        return true;
    }

    private function createUnhandledException(string $method): LogicException
    {
        return new LogicException('Cannot call "%s()" on a composite type.', $method);
    }
}
