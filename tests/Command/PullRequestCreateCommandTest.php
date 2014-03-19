<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\PullRequestCreateCommand;
use Gush\Tester\Adapter\TestAdapter;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class PullRequestCreateCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [[
                '--org' => 'gushphp',
                '--repo' => 'gush',
                '--head' => 'issue-145',
                '--template' => 'default',
                '--title' => 'Test'
            ]],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertEquals('http://github.com/gushphp/gush/pull/' . TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    public function testCommandWithIssue()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute(
            [
                '--org' => 'gushphp',
                '--repo' => 'gush',
                '--head' => 'issue-145',
                '--template' => 'default',
                '--title' => 'Test',
                '--issue' => '145'
            ],
            ['interactive' => false]
        );

        $res = trim($tester->getDisplay());
        $this->assertEquals('http://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }
}
