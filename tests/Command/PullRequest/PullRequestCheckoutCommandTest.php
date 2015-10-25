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

use Gush\Command\PullRequest\PullRequestCheckoutCommand;
use Gush\Tests\Command\CommandTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestCheckoutCommandTest extends CommandTestCase
{
    const PULL_REQUEST_NUMBER = 20;

    public function testCheckoutBranch()
    {
        $command = new PullRequestCheckoutCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }
        );

        $tester->execute(['pr_number' => self::PULL_REQUEST_NUMBER]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Successfully checked-out pull-request https://github.com/gushphp/gush/pull/'.self::PULL_REQUEST_NUMBER." in 'head_ref'",
            $display
        );
    }

    public function testCheckoutBranchWithExistingSourceBranch()
    {
        $command = new PullRequestCheckoutCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(true)->reveal());
                $helperSet->set($this->getGitConfigHelper(true, true)->reveal());
            }
        );

        $tester->execute(['pr_number' => self::PULL_REQUEST_NUMBER]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Successfully checked-out pull-request https://github.com/gushphp/gush/pull/'.self::PULL_REQUEST_NUMBER." in 'head_ref'",
            $display
        );
    }

    public function testCheckoutBranchWithExistingSourceBranchRemoteMismatchFails()
    {
        $command = new PullRequestCheckoutCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(true, false)->reveal());
                $helperSet->set($this->getGitConfigHelper(true, false)->reveal());
            }
        );

        $this->setExpectedException(
            'Gush\Exception\UserException',
            'A local branch named "head_ref" already exists but it\'s remote is not "cordoval"'
        );

        $tester->execute(['pr_number' => self::PULL_REQUEST_NUMBER]);
    }

    private function getLocalGitHelper($localBranchExists = false, $remoteMatches = true)
    {
        $helper = $this->getGitHelper();

        $helper->guardWorkingTreeReady()->willReturn();
        $helper->remoteUpdate('cordoval')->shouldBeCalled();
        $helper->branchExists(Argument::any())->willReturn(false);

        if ($localBranchExists) {
            $helper->branchExists(Argument::any())->willReturn(true);

            if ($remoteMatches) {
                $helper->checkout('head_ref')->shouldBeCalled();
                $helper->pullRemote('cordoval', 'head_ref')->shouldBeCalled();
            }
        } else {
            $helper->branchExists(Argument::any())->willReturn(false);
            $helper->checkout('cordoval/head_ref')->shouldBeCalled();
            $helper->checkout('head_ref', true)->shouldBeCalled();
        }

        return $helper;
    }

    protected function getGitConfigHelper($localBranchExists = false, $remoteMatches = false)
    {
        $helper = parent::getGitConfigHelper();

        $helper->ensureRemoteExists('cordoval', 'gush')->shouldBeCalled();

        if (!$localBranchExists) {
            $helper->setGitConfig('branch.head_ref.remote', 'cordoval', true)->shouldBeCalled();
        } else {
            $helper->getGitConfig('branch.head_ref.remote')->willReturn($remoteMatches ? 'cordoval' : 'someone');
        }

        return $helper;
    }
}
