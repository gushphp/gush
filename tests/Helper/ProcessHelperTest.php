<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\ProcessHelper;
use Symfony\Component\Console\Output\BufferedOutput;

class ProcessHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $helper;

    public function setUp()
    {
        $this->helper = new ProcessHelper();
        $this->helper->setOutput(new BufferedOutput());
    }

    /**
     * @test
     */
    public function runs_commands()
    {
        $this->helper->runCommands(
            [
                [
                    'line' => ['echo', '"hello" it\'s me'],
                    'allow_failures' => false,
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function run_commands()
    {
        self::assertEquals('"hello" it\'s me', $this->helper->runCommand(['echo', '"hello" it\'s me']));
        self::assertEquals('"hello" it\'s me', $this->helper->runCommand('echo "\"hello\" it\'s me"'));
    }
}
