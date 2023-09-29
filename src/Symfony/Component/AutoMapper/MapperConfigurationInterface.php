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

interface MapperConfigurationInterface
{
    public function process(MapperGeneratorMetadataInterface $metadata): void;

    public function getSource(): string;

    public function getTarget(): string;
}
