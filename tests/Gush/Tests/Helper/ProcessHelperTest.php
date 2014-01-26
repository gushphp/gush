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

use Gush\Helper\ProcessHelper;

class ProcessHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $helper;

    public function setUp()
    {
        $this->helper = new ProcessHelper();
    }

    public function testRunCommands()
    {
        $this->helper->runCommands([
            [
                'line' => 'echo "hello"',
                'allow_failures' => true
            ]
        ]);
    }
}
