<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;

class ObjectTypeTest extends TestCase
{
    public function testToString()
    {
        $this->assertSame(self::class, (string) new ObjectType(self::class));
    }

    public function testIsBuiltinType()
    {
        $this->assertFalse((new ObjectType(self::class))->isNullable());
        $this->assertFalse((new ObjectType(self::class))->isBuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertTrue((new ObjectType(self::class))->isBuiltinType(BuiltinTypeEnum::OBJECT));
    }
}
