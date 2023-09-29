<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AutoMapper\CacheWarmup;

use Symfony\Component\AutoMapper\Exception\CacheWarmupDataException;

final class CacheWarmupData
{
    public function __construct(
        public readonly string $source, 
        public readonly string $target,
    ) {
        if (!$this->isValid($source) || !$this->isValid($target)) {
            throw CacheWarmupDataException::sourceOrTargetDoesNoExist($source, $target);
        }

        if ($target === $source) {
            throw CacheWarmupDataException::sourceAndTargetAreEquals($source);
        }
    }

    /**
     * @param array{source: string, target: string} $array
     */
    public static function fromArray(array $array): self
    {
        return new self($array['source'], $array['target']);
    }

    private function isValid(string $arrayOrClass): bool
    {
        return $arrayOrClass === 'array' || class_exists($arrayOrClass);
    }
}