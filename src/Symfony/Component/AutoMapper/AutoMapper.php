<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AutoMapper;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\AutoMapper\Exception\NoMappingFoundException;
use Symfony\Component\AutoMapper\Extractor\FromSourceMappingExtractor;
use Symfony\Component\AutoMapper\Extractor\FromTargetMappingExtractor;
use Symfony\Component\AutoMapper\Extractor\SourceTargetMappingExtractor;
use Symfony\Component\AutoMapper\Generator\Generator;
use Symfony\Component\AutoMapper\Loader\ClassLoaderInterface;
use Symfony\Component\AutoMapper\Loader\EvalLoader;
use Symfony\Component\AutoMapper\Transformer\ArrayTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\BuiltinTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\ChainTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\DateTimeTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\EnumTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\MultipleTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\NullableTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\ObjectTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\SymfonyUidTransformerFactory;
use Symfony\Component\AutoMapper\Transformer\UniqueTypeTransformerFactory;
use PhpParser\ParserFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * Maps a source data structure (object or array) to a target one.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AutoMapper implements AutoMapperInterface, AutoMapperRegistryInterface, MapperGeneratorMetadataRegistryInterface
{
    /** @var GeneratedMapper[] */
    private array $mapperRegistry = [];

    /** @var array<string, array<string, MapperGeneratorMetadataInterface>> */
    private array $metadata = [];

    public function __construct(
        private readonly ClassLoaderInterface $classLoader,
        private readonly ?MapperGeneratorMetadataFactoryInterface $mapperConfigurationFactory = null,
    ) {
    }

    public function map(null|array|object $source, string|array|object $target, array $context = []): null|array|object
    {
        $guessedSource = $guessedTarget = null;

        if (null === $source) {
            return null;
        }

        if (\is_object($source)) {
            $guessedSource = $source::class;
        } elseif (\is_array($source)) {
            $guessedSource = 'array';
        }

        if (null === $guessedSource) {
            throw new NoMappingFoundException('Cannot map this value, source is neither an object or an array.');
        }

        if (\is_object($target)) {
            $guessedTarget = $target::class;
            $context[MapperContext::TARGET_TO_POPULATE] = $target;
        } elseif (\is_array($target)) {
            $guessedTarget = 'array';
            $context[MapperContext::TARGET_TO_POPULATE] = $target;
        } elseif (\is_string($target)) {
            $guessedTarget = $target;
        }

        if ('array' === $guessedSource && 'array' === $guessedTarget) {
            throw new NoMappingFoundException('Cannot map this value, both source and target are array.');
        }

        return $this->getMapper($guessedSource, $guessedTarget)->map($source, $context);
    }

    public function getMapper(string $source, string $target): MapperInterface
    {
        $metadata = $this->getMetadata($source, $target);

        if (null === $metadata) {
            throw new NoMappingFoundException('No mapping found for source '.$source.' and target '.$target);
        }

        $className = $metadata->getMapperClassName();

        if (\array_key_exists($className, $this->mapperRegistry)) {
            return $this->mapperRegistry[$className];
        }

        if (!class_exists($className)) {
            $this->classLoader->loadClass($metadata);
        }

        $this->mapperRegistry[$className] = new $className();
        $this->mapperRegistry[$className]->injectMappers($this);

        foreach ($metadata->getCallbacks() as $property => $callback) {
            $this->mapperRegistry[$className]->addCallback($property, $callback);
        }

        return $this->mapperRegistry[$className];
    }

    public function hasMapper(string $source, string $target): bool
    {
        return null !== $this->getMetadata($source, $target);
    }

    public function register(MapperGeneratorMetadataInterface $configuration): void
    {
        $this->metadata[$configuration->getSource()][$configuration->getTarget()] = $configuration;
    }

    public function addMapperConfiguration(MapperConfigurationInterface $mapperConfiguration): void
    {
        $metadata = $this->getMetadata($mapperConfiguration->getSource(), $mapperConfiguration->getTarget());
        $mapperConfiguration->process($metadata);
    }

    public function getMetadata(string $source, string $target): ?MapperGeneratorMetadataInterface
    {
        if (!isset($this->metadata[$source][$target])) {
            if (null === $this->mapperConfigurationFactory) {
                return null;
            }

            $this->register($this->mapperConfigurationFactory->create($this, $source, $target));
        }

        return $this->metadata[$source][$target];
    }

    /**
     * Create an automapper.
     */
    public static function create(
        bool $private = true,
        ClassLoaderInterface $loader = null,
        AdvancedNameConverterInterface $nameConverter = null,
        string $classPrefix = 'Mapper_',
        bool $attributeChecking = true,
        bool $autoRegister = true,
        string $dateTimeFormat = \DateTimeInterface::RFC3339,
        array $customTransformerFactories = [],
        bool $allowReadOnlyTargetToPopulate = false
    ): self {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        if (null === $loader) {
            $loader = new EvalLoader(new Generator(
                (new ParserFactory())->create(ParserFactory::PREFER_PHP7),
                new ClassDiscriminatorFromClassMetadata($classMetadataFactory),
                $allowReadOnlyTargetToPopulate,
            ));
        }

        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($private) {
            $flags |= ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE;
        }

        $reflectionExtractor = new ReflectionExtractor(accessFlags: $flags);

        $phpStanExtractor = new PhpStanExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpStanExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        $transformerFactory = new ChainTransformerFactory();
        $sourceTargetMappingExtractor = new SourceTargetMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $classMetadataFactory
        );

        $fromTargetMappingExtractor = new FromTargetMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $classMetadataFactory,
            $nameConverter
        );

        $fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $classMetadataFactory,
            $nameConverter
        );

        $autoMapper = $autoRegister ?
            new self(
                $loader,
                new MapperGeneratorMetadataFactory(
                    $sourceTargetMappingExtractor,
                    $fromSourceMappingExtractor,
                    $fromTargetMappingExtractor,
                    $classPrefix,
                    $attributeChecking,
                    $dateTimeFormat,
                )
            )
            : new self($loader);

        $transformerFactory->addTransformerFactory(new MultipleTransformerFactory());
        $transformerFactory->addTransformerFactory(new NullableTransformerFactory());
        $transformerFactory->addTransformerFactory(new UniqueTypeTransformerFactory());
        $transformerFactory->addTransformerFactory(new DateTimeTransformerFactory());
        $transformerFactory->addTransformerFactory(new BuiltinTransformerFactory());
        $transformerFactory->addTransformerFactory(new ArrayTransformerFactory());
        $transformerFactory->addTransformerFactory(new ObjectTransformerFactory($autoMapper));
        $transformerFactory->addTransformerFactory(new EnumTransformerFactory());

        foreach ($customTransformerFactories as $factory) {
            $transformerFactory->addTransformerFactory($factory);
        }

        if (class_exists(AbstractUid::class)) {
            $transformerFactory->addTransformerFactory(new SymfonyUidTransformerFactory());
        }

        return $autoMapper;
    }
}
