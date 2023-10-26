<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo;

use Symfony\Component\TypeInfo\Type;

/**
 * Type Extractor Interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface PropertyTypeExtractorInterface
{
    /**
     * Gets types of a property.
     *
     * @deprecated since Symfony 7.1, use "getType" instead.
     *
     * @return Type[]|null
     */
    public function getTypes(string $class, string $property, array $context = []): ?array;

    /**
     * Gets type of a property.
     */
    public function getType(string $class, string $property, array $context = []): ?Type;
}
