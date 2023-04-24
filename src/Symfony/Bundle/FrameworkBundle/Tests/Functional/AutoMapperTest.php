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

use Symfony\Bundle\FrameworkBundle\Tests\Fixtures\AutoMapper\Bar;
use Symfony\Bundle\FrameworkBundle\Tests\Fixtures\AutoMapper\Foo;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AutoMapperTest extends AbstractWebTestCase
{
    public function testMapArrayOfObject()
    {
        static::bootKernel(['test_case' => 'AutoMapper']);
        $cacheDirectory = static::getContainer()->getParameter('automapper.cache_dir');

        $this->assertFileExists($cacheDirectory . '/Symfony_Mapper_array_Symfony_Bundle_FrameworkBundle_Tests_Fixtures_AutoMapper_Foo.php');
        $this->assertFileExists($cacheDirectory . '/Symfony_Mapper_array_Symfony_Bundle_FrameworkBundle_Tests_Fixtures_AutoMapper_Bar.php');

        $result = static::getContainer()->get('automapper.alias')->map(['bars' => [['id' => 1], ['id' => 2]]], Foo::class);

        $bar1 = new Bar();
        $bar1->id = 1;
        $bar2 = new Bar();
        $bar2->id = 2;

        $expected = new Foo();
        $expected->bars = [$bar1, $bar2];

        $this->assertEquals($expected, $result);
    }
}

