<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AutoMapper\Exception;

use Symfony\Component\AutoMapper\MapperContext;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class ReadOnlyTargetException extends RuntimeException
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Cannot use readonly class as an object to populate. You can opt-out this behavior by using the context "%s"', MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE), $code, $previous);
    }
}
