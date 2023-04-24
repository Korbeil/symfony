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

/**
 * @internal
 */
final class ConfigurationCacheWarmerLoader implements CacheWarmerLoaderInterface
{
    private array $mappersToGenerateOnWarmup = [];

    /**
     * @param list<array{source: string, target: string}> $mappersToGenerateOnWarmup
     */
    public function __construct(array $mappersToGenerateOnWarmup = [])
    {
        foreach ($mappersToGenerateOnWarmup as $mapperToGenerateOnWarmup) {
            $this->addMapperToGenerateOnWarmup($mapperToGenerateOnWarmup);
        }
    }

    /**
     * @param array{source: string, target: string} $mapperToGenerateOnWarmup
     */
    public function addMapperToGenerateOnWarmup(array $mapperToGenerateOnWarmup): void
    {
        $this->mappersToGenerateOnWarmup[] = $mapperToGenerateOnWarmup;
    }

    public function loadCacheWarmupData(): iterable
    {
        foreach ($this->mappersToGenerateOnWarmup as $mapperToGenerate) {
            yield CacheWarmupData::fromArray($mapperToGenerate);
        }
    }
}