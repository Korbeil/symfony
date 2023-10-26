<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional;

use Symfony\Component\TypeInfo\Type;

class PropertyInfoTest extends AbstractWebTestCase
{
    public function testPhpDocPriority()
    {
        static::bootKernel(['test_case' => 'Serializer']);

        $this->assertEquals(Type::list(Type::int()), static::getContainer()->get('property_info')->getType(Dummy::class, 'codes'));
    }
}

class Dummy
{
    /**
     * @param int[] $codes
     */
    public function setCodes(array $codes)
    {
    }
}
