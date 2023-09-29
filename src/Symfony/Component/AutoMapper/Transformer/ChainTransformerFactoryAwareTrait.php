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

/**
 * Transformer factory that needs to be aware of the chain transformer factory
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
trait ChainTransformerFactoryAwareTrait
{
    protected ChainTransformerFactory $chainTransformerFactory;

    public function setChainTransformerFactory(ChainTransformerFactory $chainTransformerFactory): void
    {
        $this->chainTransformerFactory = $chainTransformerFactory;
    }
}
