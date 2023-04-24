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

use Symfony\Component\AutoMapper\Extractor\PropertyMapping;
use Symfony\Component\AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;

/**
 * Transform a \DateTimeInterface object to a string.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class SymfonyUidToStringTransformer implements TransformerInterface
{
    public function __construct(
        private readonly bool $isUlid,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        if ($this->isUlid) {
            return [
                // ulid
                new Expr\MethodCall($input, 'toBase32'),
                [],
            ];
        }

        return [
            // uuid
            new Expr\MethodCall($input, 'toRfc4122'),
            [],
        ];
    }
}
