<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\tests\Helper;

use Gush\Helper\GitRepoHelper;

class GitRepoHelperTest extends \PHPUnit_Framework_TestCase
{
    public function provideValidateEnum()
    {
        return [
            ['issue', 'filter', 'assigned'],
            ['foo', null, null, 'Unknown enum domain'],
            ['issue', 'foo', null, 'Unknown enum type'],
            ['issue', 'filter', 'foo', 'Unknown value'],
        ];
    }

    /**
     * @test
     * @dataProvider provideValidateEnum
     */
    public function validates_enum_with_specs($domain, $type = null, $value = null, $exceptionMessage = null)
    {
        if (null !== $exceptionMessage) {
            $this->setExpectedException('InvalidArgumentException', $exceptionMessage);
        }

        GitRepoHelper::validateEnum($domain, $type, $value);
    }

    /**
     * @test
     */
    public function validates_enums()
    {
        $enums = [
            'filter' => 'assigned',
            'state' => 'open',
        ];

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input
            ->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function ($key) use ($enums) {
                return $enums[$key];
            }))
        ;

        $res = GitRepoHelper::validateEnums($input, 'issue', ['filter', 'state']);

        $this->assertEquals($enums, $res);
    }

    /**
     * @test
     */
    public function formats_enums()
    {
        foreach (GitRepoHelper::$enum as $domain => $type) {
            foreach (array_keys($type) as $name) {
                $res = GitRepoHelper::formatEnum($domain, $name);
                $this->assertNotNull($res);
            }
        }
    }
}
