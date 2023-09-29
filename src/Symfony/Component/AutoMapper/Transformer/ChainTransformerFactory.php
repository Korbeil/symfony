<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AutoMapper\Transformer;

use Symfony\Component\AutoMapper\MapperMetadataInterface;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ChainTransformerFactory implements TransformerFactoryInterface
{
    /** @var array<int, TransformerFactoryInterface[]> $factories */
    private array $factories = [];

    /** @var TransformerFactoryInterface[]|null */
    private ?array $sorted = null;

    public function __construct(
        /** @var array<int, TransformerFactoryInterface[]> $factories */
        array $factories = [],
    ) {
        if (\count($factories) > 0) {
            foreach ($factories as $priority => $factory) {
                $this->addTransformerFactory($factory, $priority);
            }
        }
    }

    /**
     * Biggest priority is MultipleTransformerFactory with 128, so default priority will be bigger in order to
     * be used before it, 256 should be enough.
     */
    public function addTransformerFactory(TransformerFactoryInterface $transformerFactory, int $priority = 256): void
    {
        if ($this->hasTransformerFactory($transformerFactory)) {
            return;
        }

        $this->sorted = null;

        if ($transformerFactory instanceof ChainTransformerFactoryAwareInterface) {
            $transformerFactory->setChainTransformerFactory($this);
        }
        if ($transformerFactory instanceof PrioritizedTransformerFactoryInterface) {
            $priority = $transformerFactory->getPriority();
        }

        if (!\array_key_exists($priority, $this->factories)) {
            $this->factories[$priority] = [];
        }
        $this->factories[$priority][] = $transformerFactory;
    }

    public function hasTransformerFactory(TransformerFactoryInterface $transformerFactory): bool
    {
        $this->sortFactories();

        $transformerFactoryClass = $transformerFactory::class;
        foreach ($this->sorted as $factory) {
            if (is_a($factory, $transformerFactoryClass)) {
                return true;
            }
        }

        return false;
    }

    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        $this->sortFactories();

        foreach ($this->sorted as $factory) {
            $transformer = $factory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);

            if (null !== $transformer) {
                return $transformer;
            }
        }

        return null;
    }

    private function sortFactories(): void
    {
        if (null === $this->sorted) {
            $this->sorted = [];
            krsort($this->factories);

            foreach ($this->factories as $prioritisedFactories) {
                foreach ($prioritisedFactories as $factory) {
                    $this->sorted[] = $factory;
                }
            }
        }
    }
}
