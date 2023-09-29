<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace Symfony\Component\AutoMapper\Exception;

/**
 * @internal
 */
final class CacheWarmupDataException extends \RuntimeException
{
    private function __construct(string $message, string $source, string $target)
    {
        parent::__construct(
            "Invalid automapper warmup configuration: $message. {source: \"$source\", target: \"$target\"} given."
        );
    }

    public static function sourceAndTargetAreEquals(string $value): self
    {
        return new self('source and target must be different', $value, $value);
    }

    public static function sourceOrTargetDoesNoExist(string $source, string $target): self
    {
        return new self('source and target must be "array" or a valid class name', $source, $target);
    }
}