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

use Gush\Command\PullRequest\PullRequestSquashCommand;
use Gush\Exception\UserException;
use Gush\Helper\GitHelper;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestSquashCommandTest extends CommandTestCase
{
    public function testSquashesCommitsAndForcePushesBranch()
    {
        $tester = $this->getCommandTester(new PullRequestSquashCommand());
        $tester->execute(['pr_number' => 10]);

        $this->assertCommandOutputMatches('Pull-request has been squashed!', $tester->getDisplay());
    }

    public function testDoNotUseLocalBranchWhenIsMissing()
    {
        $command = new PullRequestSquashCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, false, false)->reveal());
            }
        );

        $tester->execute(['pr_number' => 10]);

        $this->assertCommandOutputMatches('Pull-request has been squashed!', $tester->getDisplay());
    }

    public function testInformPullIsPerformed()
    {
        $command = new PullRequestSquashCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, true, GitHelper::STATUS_NEED_PULL)->reveal());
            }
        );

        $tester->execute(['pr_number' => 10]);

        $this->assertCommandOutputMatches(
            [
                'Pull-request has been squashed!',
                'Your local branch "head_ref" is outdated, running git pull',
            ],
            $tester->getDisplay()
        );
    }

    public function testFailsWhenBranchHasDiverged()
    {
        $command = new PullRequestSquashCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, true, GitHelper::STATUS_DIVERGED)->reveal());
            }
        );

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Your local and remote version of branch "head_ref" have differed.');

        $tester->execute(['pr_number' => 10]);
    }

    public function testWarnPossibleAccessDenied()
    {
        $command = new PullRequestSquashCommand();
        $tester = $this->getCommandTester(
            $command,
            [
                'adapters' => [
                    'github_enterprise' => [
                        'authentication' => [
                            'username' => 'someone',
                            'password' => 'very-un-secret',
                        ],
                    ],
                ],
            ],
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, false, false)->reveal());
            }
        );

        $this->setExpectedCommandInput($command, "yes\n");

        $tester->execute(['pr_number' => 10]);

        $this->assertCommandOutputMatches(
            [
                'Pull-request has been squashed!',
                'You are not the owner of the repository pull-requests\'s source-branch.',
                'Make sure you have push access to the "cordoval/gush" repository before you continue.',
                'Do you want to squash the pull-request and push?',
            ],
            $tester->getDisplay()
        );
    }

    public function testDoesNotWarnPossibleAccessDeniedWhenBranchIsOnSameOrg()
    {
        $command = new PullRequestSquashCommand();
        $tester = $this->getCommandTester(
            $command,
            [
                'adapters' => [
                    'github_enterprise' => [
                        'authentication' => [
                            'username' => 'someone',
                            'password' => 'very-un-secret',
                        ],
                    ],
                ],
            ],
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, false, false)->reveal());
            }
        );

        // Prevent hanging when the test fails.
        $this->setExpectedCommandInput($command, "no\n");

        $tester->execute(['pr_number' => 10, '--org' => 'cordoval']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches('Pull-request has been squashed!', $display);
        $this->assertNotContains('You are not the owner of the repository.', $display);
    }

    protected function getGitConfigHelper()
    {
        $helper = parent::getGitConfigHelper();
        $helper->ensureRemoteExists('gushphp', 'gush')->shouldBeCalled();
        $helper->ensureRemoteExists('cordoval', 'gush')->shouldBeCalled();

        return $helper;
    }

    protected function getGitHelper($isGitDir = true, $branchExists = true, $status = GitHelper::STATUS_UP_TO_DATE)
    {
        $helper = parent::getGitHelper($isGitDir);
        $helper->remoteUpdate('gushphp')->shouldBeCalled();
        $helper->remoteUpdate('cordoval')->shouldBeCalled();

        $helper->stashBranchName()->shouldBeCalled();
        $helper->branchExists('head_ref')->willReturn($branchExists);

        $helper->restoreStashedBranch()->shouldBeCalled();

        if ($branchExists) {
            $helper->checkout('head_ref')->shouldBeCalled();
            $helper->getRemoteDiffStatus("cordoval", "head_ref")->willReturn($status);

            // Return when this status is given because expectations are checked after
            // An exception was expected.
            if (GitHelper::STATUS_DIVERGED === $status) {
                return $helper;
            }

            if (GitHelper::STATUS_NEED_PULL === $status) {
                $helper->pullRemote('cordoval', 'head_ref')->shouldBeCalled();
            }
        } else {
            $helper->checkout('cordoval/head_ref')->shouldBeCalled();
            $helper->checkout('head_ref', true)->shouldBeCalled();
        }

        $helper->squashCommits('gushphp/base_ref', 'head_ref', false)->shouldBeCalled();
        $helper->pushToRemote('cordoval', 'head_ref', false, true)->shouldBeCalled();

        return $helper;
    }
}
