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

class PullRequestCreateCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [
                [
                    '--org' => 'gushphp',
                    '--repo' => 'gush',
                    '--source-branch' => 'issue-145',
                    '--template' => 'default',
                    '--title' => 'Test',
                ]
            ],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {
        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper(), 'git');
        $this->expectsConfig();
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommandWithIssue($args)
    {
        $args['--issue'] = '145';

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $this->expectsConfig();
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @dataProvider provideCommand
     */
    public function testSourceOrgAutodetect($args)
    {
        $args['--verbose'] = true;

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper(), 'git');
        $this->expectsConfig();
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

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper(), 'git');
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

    private function expectGitHelper()
    {
        $git = $this->getMockBuilder('Gush\Helper\GitHelper')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $git->expects($this->once())
            ->method('getFirstCommitTitle')
            ->with('master', 'issue-145')
            ->will($this->returnValue('some good title'))
        ;

        return $git;
    }
}
