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

use PhpParser\Node\Expr;

/**
 * Transformer array decorator.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ArrayTransformer extends AbstractArrayTransformer
{
    protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr
    {
        if ($assignByRef) {
            return new Expr\AssignRef(new Expr\ArrayDimFetch($valuesVar), $outputVar);
        }

        return new Expr\Assign(new Expr\ArrayDimFetch($valuesVar), $outputVar);
    }
}
