<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestCreateCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestCreateCommandTest extends CommandTestCase
{
    public function testOpenPullRequestWithExistingRemoteBranch()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }
        );

        $this->setExpectedCommandInput(
            $command,
            [
                'My amazing feature', // title
                'master', // 'branch'
                '', // 'bug_fix'
                'yes', // 'new_feature'
                'no', // bc_breaks
                'no', // deprecations
                'yes', // tests_pass
                '#000', // fixed_tickets
                'MIT', // license
                'NA', // doc_pr
                'My Description', // description
            ]
        );

        $tester->execute(['--template' => 'symfony']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'cordoval wants to merge 1 commit into gushphp/gush:master from cordoval:issue-145',
                'Opened pull request https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER,
            ],
            $display
        );

        $pr = $command->getAdapter()->getPullRequest(TestAdapter::PULL_REQUEST_NUMBER);

        $this->assertEquals('My amazing feature', $pr['title']);
        $this->assertContains('|Bug fix?', $pr['body']);
        $this->assertContains('My Description', $pr['body']);

        $this->assertEquals('master', $pr['base']['ref']);
        $this->assertEquals('cordoval', $pr['head']['user']);
        $this->assertEquals('issue-145', $pr['head']['ref']);
    }

    public function testOpenPullRequestWithExistingRemoteBranchNoInteractive()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }
        );

        $tester->execute([], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'cordoval wants to merge 1 commit into gushphp/gush:master from cordoval:issue-145',
                'Opened pull request https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER,
            ],
            $display
        );
    }

    public function testOpenPullRequestWithSourceOptionsProvided()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('sstok', 'gush', 'feat-adapters')->reveal());
                $helperSet->set($this->getGitConfigHelper('sstok')->reveal());
            }
        );

        // use the default title
        $this->setExpectedCommandInput($command, "\nInception\n");

        $tester->execute(
            [
                '--template' => 'default',
                '--source-org' => 'user',
                '--source-branch' => 'feat-adapters',
                '--title' => 'Refactored adapter support',
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'user wants to merge 1 commit into gushphp/gush:master from user:feat-adapters',
                'Opened pull request https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER,
            ],
            $display
        );

        $pr = $command->getAdapter()->getPullRequest(TestAdapter::PULL_REQUEST_NUMBER);

        $this->assertEquals('Refactored adapter support', $pr['title']);
        $this->assertContains('Inception', $pr['body']);

        $this->assertEquals('master', $pr['base']['ref']);
        $this->assertEquals('user', $pr['head']['user']);
        $this->assertEquals('feat-adapters', $pr['head']['ref']);
    }

    public function testOpenPullRequestWithCustomTemplate()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            [
                'repo_adapter' => 'github_enterprise',
                'issue_adapter' => 'github_enterprise',
                'repo_org' => 'gushphp',
                'repo_name' => 'gush',

                'table-pr' => [
                    'marco' => ['Marco?', ''],
                    'myq' => ['My question', 'y'],
                ],
            ],
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }
        );

        $this->setExpectedCommandInput(
            $command,
            [
                '', // title
                'polo', // marco
                '', // myq
                'My Description', // description
            ]
        );

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'cordoval wants to merge 1 commit into gushphp/gush:master from cordoval:issue-145',
                'Marco?',
                'My question',
                'Description (enter "e" to open editor)',
            'Opened pull request https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER,
            ],
            $display
        );

        $pr = $command->getAdapter()->getPullRequest(TestAdapter::PULL_REQUEST_NUMBER);

        $this->assertEquals('Some good title', $pr['title']);
        $this->assertContains('My Description', $pr['body']);
        $this->assertRegExp('{\|Marco\?\s*\|polo\\\s*|}', $pr['body']);
        $this->assertRegExp('{\|My question\\\s*|y\s*\|}', $pr['body']);

        $this->assertEquals('master', $pr['base']['ref']);
        $this->assertEquals('cordoval', $pr['head']['user']);
        $this->assertEquals('issue-145', $pr['head']['ref']);
    }

    public function testOpenPullRequestAutoPushMissingBranch()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('someone')->reveal());
                $helperSet->set($this->getGitConfigHelper('someone')->reveal());
            }
        );

        // use the default title
        $this->setExpectedCommandInput($command, "\nMy description\n");

        $tester->execute(
            [
                '--template' => 'default',
                '--source-org' => 'someone',
            ],
            ['interactive' => true]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'someone wants to merge 1 commit into gushphp/gush:master from someone:issue-145',
                'Branch "issue-145" was pushed to "someone".',
                'Opened pull request https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER,
            ],
            $display
        );

        $this->assertNotContains('Do you want to push the branch now?', $display);

        $pr = $command->getAdapter()->getPullRequest(TestAdapter::PULL_REQUEST_NUMBER);

        $this->assertEquals('Some good title', $pr['title']);
    }

    public function testCannotOpenPullRequestForNonExistentBranch()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('someone')->reveal());
                $helperSet->set($this->getGitConfigHelper('someone')->reveal());
            }
        );

        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Cannot open pull-request, remote branch "not-my-branch" does not exist in "someone/gush".'
        );

        $tester->execute(
            [
                '--template' => 'default',
                '--source-org' => 'someone',
                '--source-branch' => 'not-my-branch',
            ]
        );
    }

    public function testCannotOpenPullRequestWithNoCommits()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('someone', 'gush', 'issue-145', 0)->reveal());
                $helperSet->set($this->getGitConfigHelper('someone')->reveal());
            }
        );

        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Cannot open pull-request because there are no commits between current branch ("not-my-branch") and "gushphp/gush:master".'
        );

        $tester->execute(
            [
                '--template' => 'default',
                '--source-org' => 'someone',
                '--source-branch' => 'not-my-branch',
            ]
        );
    }

    public function testOpenPullRequestUsingBaseAsDefaultBranch()
    {
        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('cordoval', 'gush', 'issue-145', 1, 'development')->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }
        );

        $this->setExpectedCommandInput(
            $command,
            [
                'My amazing feature', // title
                null, // 'branch'
                'no', // 'bug_fix'
                'yes', // 'new_feature'
                'no', // bc_breaks
                'no', // deprecations
                'yes', // tests_pass
                'n/a', // fixed_tickets
                'MIT', // license
                'n/a', // doc_pr
                'My Description', // description
            ]
        );

        $tester->execute(['--template' => 'symfony', '--base' => 'development']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'cordoval wants to merge 1 commit into gushphp/gush:development from cordoval:issue-145.',
                'Opened pull request https://github.com/gushphp/gush/pull/'.TestAdapter::PULL_REQUEST_NUMBER,
            ],
            $display
        );

        $pr = $command->getAdapter()->getPullRequest(TestAdapter::PULL_REQUEST_NUMBER);

        $this->assertEquals('My amazing feature', $pr['title']);
        $this->assertContains('|development', $pr['body']);

        $this->assertEquals('development', $pr['base']['ref']);
        $this->assertEquals('cordoval', $pr['head']['user']);
        $this->assertEquals('issue-145', $pr['head']['ref']);
    }

    private function getLocalGitHelper($sourceOrg = 'cordoval', $sourceRepo = 'gush', $branch = 'issue-145', $commitCount = 1, $baseBranch = 'master')
    {
        $helper = $this->getGitHelper();

        $helper->getFirstCommitTitle(sprintf('gushphp/%s', $baseBranch), 'issue-145')->willReturn('Some good title');
        $helper->getActiveBranchName()->willReturn('issue-145');

        $helper->getCommitCountBetweenLocalAndBase(Argument::any(), $baseBranch, Argument::any())->willReturn($commitCount);

        $helper->remoteBranchExists(Argument::any(), Argument::any())->willReturn(false);
        $helper->remoteBranchExists('git@github.com:cordoval/gush.git', $branch)->willReturn(true);
        $helper->remoteUpdate('gushphp')->shouldBeCalled();

        $helper->branchExists(Argument::any())->willReturn(false);
        $helper->branchExists($branch)->will(
            function () use ($helper, $sourceOrg, $sourceRepo, $branch) {
                $helper->remoteUpdate($sourceOrg)->shouldBeCalled();
                $helper->pushToRemote($sourceOrg, $branch, true)->shouldBeCalled();

                return true;
            }
        );

        return $helper;
    }

    protected function getGitConfigHelper($sourceOrg = 'cordoval', $sourceRepo = 'gush')
    {
        $helper = parent::getGitConfigHelper();
        $helper->ensureRemoteExists('gushphp', 'gush')->shouldBeCalled();

        $helper->remoteExists($sourceOrg, $sourceRepo)->willReturn();
        $helper->ensureRemoteExists($sourceOrg, $sourceRepo)->willReturn();

        return $helper;
    }
}
