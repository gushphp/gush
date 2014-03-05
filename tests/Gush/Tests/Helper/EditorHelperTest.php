<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\EditorHelper;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Daniel Leech <daniel@dantleech.com>
 */
class EditorHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $helper;

    public function setUp()
    {
        $this->helper = new EditorHelper();
        putenv('EDITOR=cat');
    }

    /**
     * @test
     */
    public function it_outputs_from_a_string()
    {
        $oneTwoThree = <<<EOT
One
Two
Three
EOT;
        $res = $this->helper->fromString($oneTwoThree);

        $this->assertEquals($oneTwoThree, $res);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_fails_to_output_when_editor_environmental_variable_is_not_set()
    {
        putenv('EDITOR');
        $this->helper->fromString('asd');
    }

    /**
     * @test
     * @dataProvider provideFromStringWithMessage
     */
    public function it_outputs_from_a_string_with_message($source, $message)
    {
        $res = $this->helper->fromStringWithMessage($source, $message);
        $this->assertSame($source, $res);
    }

    public function provideFromStringWithMessage()
    {
        return [
            [
                <<<EOT
This is some text that I want to edit
EOT
            ,
                <<<EOT
This is some text that I want the user to see in a command

OK
EOT
            ],
        ];
    }
}
