<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\AutoMapper\AutoMapper;
use Symfony\Component\AutoMapper\AutoMapperInterface;
use Symfony\Component\AutoMapper\AutoMapperRegistryInterface;
use Symfony\Component\AutoMapper\CacheWarmup\CacheWarmer;
use Symfony\Component\AutoMapper\CacheWarmup\ConfigurationCacheWarmerLoader;
use Symfony\Component\AutoMapper\Extractor\FromSourceMappingExtractor;
use Symfony\Component\AutoMapper\Extractor\FromTargetMappingExtractor;
use Symfony\Component\AutoMapper\Extractor\SourceTargetMappingExtractor;
use Symfony\Component\AutoMapper\Generator\Generator;
use Symfony\Component\AutoMapper\Loader\ClassLoaderInterface;
use Symfony\Component\AutoMapper\Loader\FileLoader;
use Symfony\Component\AutoMapper\MapperGeneratorMetadataFactory;
use Symfony\Component\AutoMapper\MapperGeneratorMetadataFactoryInterface;
use Symfony\Component\AutoMapper\MapperGeneratorMetadataRegistryInterface;
use Symfony\Component\AutoMapper\Normalizer\AutoMapperNormalizer;
use Symfony\Component\AutoMapper\Transformer\ArrayTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\BuiltinTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\ChainTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\DateTimeTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\EnumTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\MultipleTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\NullableTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\ObjectTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\SymfonyUidTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\TransformerFactoryInterface;
use Symfony\Component\AutoMapper\Transformer\UniqueTypeTransformerFactory;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('automapper', AutoMapper::class)
            ->args([
                service(ClassLoaderInterface::class),
                service(MapperGeneratorMetadataFactoryInterface::class),
            ])
        ->alias(AutoMapperInterface::class, 'automapper')
        ->alias(AutoMapperRegistryInterface::class, 'automapper')
        ->alias(MapperGeneratorMetadataRegistryInterface::class, 'automapper')

        ->set('automapper.extractor.source_target', SourceTargetMappingExtractor::class)
            ->args([
                service('property_info'),
                service('property_info.reflection_extractor'),
                service('property_info.reflection_extractor'),
                service('automapper.transformer_factory.chain'),
            ])
        ->set('automapper.extractor.from_target', FromTargetMappingExtractor::class)
            ->args([
                service('property_info'),
                service('property_info.reflection_extractor'),
                service('property_info.reflection_extractor'),
                service('automapper.transformer_factory.chain'),
                service(ClassMetadataFactoryInterface::class),
            ])
        ->set('automapper.extractor.from_source', FromSourceMappingExtractor::class)
            ->args([
                service('property_info'),
                service('property_info.reflection_extractor'),
                service('property_info.reflection_extractor'),
                service('automapper.transformer_factory.chain'),
                service(ClassMetadataFactoryInterface::class),
            ])

        ->set('automapper.metadata.generator', MapperGeneratorMetadataFactory::class)
            ->args([
                service('automapper.extractor.source_target'),
                service('automapper.extractor.from_source'),
                service('automapper.extractor.from_target'),
                param('automapper.mapper_prefix'),
                true,
                param('automapper.datetime_format'),
            ])
        ->alias(MapperGeneratorMetadataFactoryInterface::class, 'automapper.metadata.generator')

        ->set('automapper.generator', Generator::class)
            ->args([null, service(ClassDiscriminatorResolverInterface::class), false])

        ->set('automapper.loader.file', FileLoader::class)
            ->args([
                service('automapper.generator'),
                param('automapper.cache_dir'),
                param('kernel.debug'),
            ])
        ->alias(ClassLoaderInterface::class, 'automapper.loader.file')

        ->set('automapper.cache_warmer', CacheWarmer::class)
            ->args([
                service(ClassLoaderInterface::class),
                service(AutoMapperRegistryInterface::class),
                service(MapperGeneratorMetadataFactoryInterface::class),
                tagged_iterator('automapper.cache_warmer_loader'),
            ])
            ->tag('kernel.cache_warmer')
        
        ->set('automapper.configuration_cache_warmer', ConfigurationCacheWarmerLoader::class)
            ->args([[]])
            ->tag('automapper.cache_warmer_loader')

        ->set('automapper.normalizer', AutoMapperNormalizer::class)
            ->args([service('automapper')])

        ->set('automapper.transformer_factory.chain', ChainTransformerFactory::class)
            ->args([[]])
        ->alias(TransformerFactoryInterface::class, 'automapper.transformer_factory.chain')

        ->set('automapper.transformer_factory.symfony_uid', SymfonyUidTransformerFactory::class)

        ->set('automapper.transformer_factory.multiple', MultipleTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => 1002])
        ->set('automapper.transformer_factory.nullable', NullableTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => 1001])
        ->set('automapper.transformer_factory.unique_type', UniqueTypeTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => 1000])
        ->set('automapper.transformer_factory.object', ObjectTransformerFactory::class)
            ->args([service('automapper')])
            ->tag('automapper.transformer_factory',  ['priority' => -1000])
        ->set('automapper.transformer_factory.enum', EnumTransformerFactory::class)
            ->tag('automapper.transformer_factory',  ['priority' => -1001])
        ->set('automapper.transformer_factory.datetime', DateTimeTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1001])
        ->set('automapper.transformer_factory.builtin', BuiltinTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1002])
        ->set('automapper.transformer_factory.array', ArrayTransformerFactory::class)
            ->tag('automapper.transformer_factory', ['priority' => -1003])
    ;
};
