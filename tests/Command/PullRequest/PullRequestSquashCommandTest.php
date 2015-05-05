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

use Gush\Command\PullRequest\PullRequestSquashCommand;
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

    public function testDoNotResetLocalBranch()
    {
        $command = new PullRequestSquashCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(true, true, false)->reveal());
            }
        );

        $tester->execute(['pr_number' => 10, '--no-local-sync' => true]);

        $this->assertCommandOutputMatches('Pull-request has been squashed!', $tester->getDisplay());
    }

    public function testDoNotResetLocalBranchWhenIsMissing()
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
                        ]
                    ]
                ]
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
                'Do you want to squash the pull-request and push?'
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
                        ]
                    ]
                ]
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

    protected function getGitHelper($isGitFolder = true, $branchExists = true, $localSync = true)
    {
        $helper = parent::getGitHelper($isGitFolder);
        $helper->remoteUpdate('gushphp')->shouldBeCalled();
        $helper->remoteUpdate('cordoval')->shouldBeCalled();

        $helper->stashBranchName()->shouldBeCalled();
        $helper->checkout('head_ref')->shouldBeCalled();

        $helper->createTempBranch('head_ref')->willReturn('temp--head_ref');
        $helper->checkout('temp--head_ref', true)->shouldBeCalled();

        $helper->squashCommits('gushphp/base_ref', 'temp--head_ref')->shouldBeCalled();
        $helper->pushToRemote('cordoval', 'temp--head_ref:head_ref', false, true)->shouldBeCalled();

        $helper->branchExists('head_ref')->willReturn($branchExists);

        if ($localSync) {
            $helper->reset('temp--head_ref', 'hard')->shouldBeCalled();
        }

        $helper->restoreStashedBranch()->shouldBeCalled();

        return $helper;
    }
}
