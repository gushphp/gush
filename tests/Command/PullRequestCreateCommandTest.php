<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\PullRequest\PullRequestCreateCommand;
use Gush\Tester\Adapter\TestAdapter;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestCreateCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [[
                '--org'           => 'gushphp',
                '--repo'          => 'gush',
                '--source-branch' => 'issue-145',
                '--template'      => 'default',
                '--title'         => 'Test'
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

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('http://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommandWithIssue($args)
    {
        $args['--issue'] = '145';

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('http://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testSourceOrgAutodetect($args)
    {
        $args['--verbose'] = true;

        $process = $this->getMock('Gush\Helper\ProcessHelper');
        $this->expectsConfig();
        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($process, 'process');
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertContains('Making PR from cordoval:issue-145 to gushphp:master', $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testSourceOrgOption($args)
    {
        $args['--verbose']    = true;
        $args['--source-org'] = 'gushphp';

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertContains('Making PR from '.$args['--source-org'].':issue-145 to gushphp:master', $res);
    }

    private function expectsConfig()
    {
        $this->config
            ->expects($this->at(0))
            ->method('get')
            ->with('adapter')
            ->will($this->returnValue('github_enterprise'))
        ;
        $this->config
            ->expects($this->at(1))
            ->method('get')
            ->with('[adapters][github_enterprise][authentication]')
            ->will($this->returnValue(['username' => 'cordoval']))
        ;
    }
}
