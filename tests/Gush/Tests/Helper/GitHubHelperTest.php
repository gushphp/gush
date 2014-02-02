<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\GitHubHelper;

class GitHubHelperTest extends \PHPUnit_Framework_TestCase
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
     * @dataProvider provideValidateEnum
     */
    public function testValidateEnum($domain, $type = null, $value = null, $exceptionMessage = null)
    {
        if (null !== $exceptionMessage) {
            $this->setExpectedException('InvalidArgumentException', $exceptionMessage);
        }

        GitHubHelper::validateEnum($domain, $type, $value);
    }

    public function testValidateEnums()
    {
        $enums = [
            'filter' => 'assigned',
            'state' => 'open',
        ];

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function ($key) use ($enums) {
                return $enums[$key];
            }));

        $res = GitHubHelper::validateEnums($input, 'issue', array('filter', 'state'));

        $this->assertEquals($enums, $res);
    }

    public function testFormatEnums()
    {
        foreach (GitHubHelper::$enum as $domain => $type) {
            foreach (array_keys($type) as $name) {
                $res = GitHubHelper::formatEnum($domain, $name);
                $this->assertNotNull($res);
            }
        }
    }
}
