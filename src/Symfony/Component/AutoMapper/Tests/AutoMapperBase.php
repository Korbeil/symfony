<?php

namespace Symfony\Component\AutoMapper\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\AutoMapper\AutoMapper;
use Symfony\Component\AutoMapper\AutoMapperInterface;
use Symfony\Component\AutoMapper\Generator\Generator;
use Symfony\Component\AutoMapper\Loader\ClassLoaderInterface;
use Symfony\Component\AutoMapper\Loader\FileLoader;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract class AutoMapperBase extends TestCase
{
    protected AutoMapperInterface $autoMapper;
    protected ClassLoaderInterface $loader;

    protected function setUp(): void
    {
        unset($this->autoMapper, $this->loader);
        $this->buildAutoMapper();
    }

    protected function buildAutoMapper(array $customTransformerFactories = [], bool $allowReadOnlyTargetToPopulate = false): AutoMapperInterface
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/cache/');
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $this->loader = new FileLoader(new Generator(
            (new ParserFactory())->create(ParserFactory::PREFER_PHP7),
            new ClassDiscriminatorFromClassMetadata($classMetadataFactory),
            $allowReadOnlyTargetToPopulate,
        ), __DIR__ . '/cache');

        return $this->autoMapper = AutoMapper::create(true, $this->loader, customTransformerFactories: $customTransformerFactories);
    }
}
