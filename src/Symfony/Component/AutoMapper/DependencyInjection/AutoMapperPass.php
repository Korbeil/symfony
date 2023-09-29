<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AutoMapper\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AutoMapperPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('automapper.transformer_factory.chain')) {
            return;
        }

        if (!$container->findTaggedServiceIds('automapper.transformer_factory')) {
            throw new RuntimeException('You must tag at least one service as "automapper.transformer_factory" to use the "automapper" service.');
        }

        $chainTransformerFactory = $container->getDefinition('automapper.transformer_factory.chain');
        foreach ($this->findAndSortTaggedServices('automapper.transformer_factory', $container) as $transformerFactory) {
            $chainTransformerFactory->addMethodCall('addTransformerFactory', [$transformerFactory]);
        }

        $automapper = $container->getDefinition('automapper');

        foreach ($this->findAndSortTaggedServices('automapper.mapper_metadata', $container) as $mapperMetadata) {
            $automapper->addMethodCall('register', [$mapperMetadata]);
        }
        foreach ($this->findAndSortTaggedServices('automapper.mapper_configuration', $container) as $mapperConfiguration) {
            $automapper->addMethodCall('addMapperConfiguration', [$mapperConfiguration]);
        }
    }
}
