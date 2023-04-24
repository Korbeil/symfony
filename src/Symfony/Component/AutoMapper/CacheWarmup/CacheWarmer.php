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

use Symfony\Component\AutoMapper\Loader\FileLoader;
use Symfony\Component\AutoMapper\MapperGeneratorMetadataFactoryInterface;
use Symfony\Component\AutoMapper\MapperGeneratorMetadataRegistryInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
final class CacheWarmer implements CacheWarmerInterface
{
    private $fileLoader;
    private $autoMapperRegistry;
    private $mapperConfigurationFactory;
    /** @var iterable<CacheWarmerLoaderInterface> */
    private $cacheWarmerLoaders;

    /** @param iterable<CacheWarmerLoaderInterface> $cacheWarmerLoaders */
    public function __construct(
        FileLoader $fileLoader,
        MapperGeneratorMetadataRegistryInterface $autoMapperRegistry,
        MapperGeneratorMetadataFactoryInterface $mapperConfigurationFactory,
        iterable $cacheWarmerLoaders
    ) {
        $this->fileLoader = $fileLoader;
        $this->autoMapperRegistry = $autoMapperRegistry;
        $this->mapperConfigurationFactory = $mapperConfigurationFactory;
        $this->cacheWarmerLoaders = $cacheWarmerLoaders;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp($cacheDir): array
    {
        $mapperClasses = [];

        foreach ($this->cacheWarmerLoaders as $cacheWarmerLoader) {
            foreach ($cacheWarmerLoader->loadCacheWarmupData() as $cacheWarmupData) {
                $mapperClasses[] = $this->fileLoader->saveMapper(
                    $this->mapperConfigurationFactory->create(
                        $this->autoMapperRegistry,
                        $cacheWarmupData->source,
                        $cacheWarmupData->target
                    )
                );
            }
        }

        return $mapperClasses;
    }
}
