<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestCreateCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;

class PullRequestCreateCommandTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider provideCommand
     */
    public function opens_pull_request_with_arguments($args)
    {
        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper(), 'git');
        $this->expectsConfig();
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @test
     * @dataProvider provideCommand
     */
    public function opens_pull_request_from_an_issue($args)
    {
        $args['--issue'] = '145';

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $this->expectsConfig();
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertEquals('https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER, $res);
    }

    /**
     * @test
     * @dataProvider provideCommand
     */
    public function opens_pull_request_autodetecting_current_branch_and_default_master($args)
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
     * @test
     * @dataProvider provideCommand
     */
    public function opens_pull_request_to_a_specific_organization_or_username($args)
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

}
