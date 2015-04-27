<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestCreateCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;
use Prophecy\Argument;

class PullRequestCreateCommandTest extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->config->get('base')->willReturn('master');
        $this->config->get('remove-promote')->willReturn(false);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_with_arguments($args)
    {
        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper('cordoval'));
        $command->getHelperSet()->set($this->expectGitConfigHelper());

        $tester->execute($args, ['interactive' => false]);

        $url = 'https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER;
        $expected = <<<RES
Open request on gushphp/gush
============================

// This pull-request will be opened on "gushphp/gush".
// The source branch is "issue-145" on "cordoval".



[OK] Opened pull request $url
RES;

        $this->assertCommandOutputEquals($expected, $tester->getDisplay(true));
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_autodetecting_current_branch_and_default_master($args)
    {
        $args['--verbose'] = true;

        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper());
        $command->getHelperSet()->set($this->expectGitConfigHelper());

        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertContains('Making PR from cordoval:issue-145 to gushphp:master', $res);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function opens_pull_request_to_a_specific_organization_or_username($args)
    {
        $args['--verbose'] = true;
        $args['--source-org'] = 'gushphp';

        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper('gushphp', 'gush'));
        $command->getHelperSet()->set($this->expectGitConfigHelper(true, 'gushphp', 'gush'));

        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay(true));
        $this->assertContains('Making PR from '.$args['--source-org'].':issue-145 to gushphp:master', $res);
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function automatically_pushes_when_none_interactive_and_opens_pull_request($args)
    {
        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $args['--source-org'] = 'gushphp';
        $args['--source-branch'] = 'not-my-branch';

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper('gushphp', 'gush', 'not-my-branch'));
        $command->getHelperSet()->set($this->expectGitConfigHelper(true, 'gushphp'));

        $tester->execute($args, ['interactive' => false]);

        $url = 'https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER;
        $expected = <<<RES
Open request on gushphp/gush
============================

// This pull-request will be opened on "gushphp/gush".
// The source branch is "not-my-branch" on "gushphp".

[OK] Branch "not-my-branch" was pushed to "gushphp".



[OK] Opened pull request $url
RES;

        $this->assertCommandOutputEquals($expected, $tester->getDisplay(true));
    }

    /**
     * @test
     *
     * @dataProvider provideCommand
     */
    public function errors_when_remote_branch_missing_and_no_local_exists($args)
    {
        $this->expectsConfig();
        $this->config->has('table-pr')->willReturn(false);

        $args['--source-branch'] = 'not-my-branch';

        $tester = $this->getCommandTester($command = new PullRequestCreateCommand());
        $command->getHelperSet()->set($this->expectGitHelper());
        $command->getHelperSet()->set($this->expectGitConfigHelper());

        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Can not open pull-request, remote branch "not-my-branch" does not exist in "cordoval/gush".'
        );

        $tester->execute($args, ['interactive' => false]);
    }

    private function expectGitHelper($sourceOrg = 'cordoval', $sourceRepo = 'gush', $branch = 'issue-145')
    {
        $gitHelper = $this->prophet->prophesize('Gush\Helper\GitHelper');
        $gitHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitHelper->getName()->willReturn('git');

        $gitHelper->getFirstCommitTitle('master', 'issue-145')->willReturn('some good title');
        $gitHelper->getActiveBranchName()->willReturn('issue-145');

        $gitHelper->remoteBranchExists(Argument::any(), Argument::any())->willReturn(false);
        $gitHelper->remoteBranchExists('git@github.com:cordoval/gush.git', $branch)->willReturn(true);

        $gitHelper->branchExists(Argument::any())->willReturn(false);
        $gitHelper->branchExists($branch)->will(
            function () use ($gitHelper, $sourceOrg, $sourceRepo, $branch) {
                $gitHelper->remoteUpdate($sourceOrg)->shouldBeCalled();
                $gitHelper->pushToRemote($sourceOrg, $branch, true)->shouldBeCalled();

                return true;
            }
        );

        return $gitHelper->reveal();
    }

    private function expectGitConfigHelper($expected = false, $sourceOrg = 'cordoval', $sourceRepo = 'gush')
    {
        $gitConfigHelper = $this->prophet->prophesize('Gush\Helper\GitConfigHelper');
        $gitConfigHelper->setHelperSet(Argument::any())->shouldBeCalled();
        $gitConfigHelper->getName()->willReturn('git_config');

        if ($expected) {
            $gitConfigHelper->ensureRemoteExists($sourceOrg, $sourceRepo)->shouldBeCalled();
        } else {
            $gitConfigHelper->ensureRemoteExists($sourceOrg, $sourceRepo)->shouldNotBeCalled();
        }

        return $gitConfigHelper->reveal();
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
