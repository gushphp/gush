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

use Gush\Command\PullRequest\PullRequestSwitchBaseCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestSwitchBaseCommandTest extends CommandTestCase
{
    public function testSwitchPullRequestBase()
    {
        $command = new PullRequestSwitchBaseCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
            }
        );

        $tester->execute(['pr_number' => 10, 'base_branch' => 'develop']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Pull-request base-branch has been switched!',
            $display
        );
    }

    public function testSwitchPullRequestBaseForceNew()
    {
        $command = new PullRequestSwitchBaseCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
            }
        );

        $tester->execute(
            ['pr_number' => 10, 'base_branch' => 'develop', '--force-new-pr' => true]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Pull-request base-branch could not be switched, a new pull-request has been opened instead: '.
            $command->getAdapter()->getPullRequestUrl(TestAdapter::PULL_REQUEST_NUMBER),
            $display
        );

        $pr = $command->getAdapter()->getPullRequest(TestAdapter::PULL_REQUEST_NUMBER);

        $this->assertEquals('Write a behat test to launch strategy', $pr['title']);
        $this->assertEquals('Help me conquer the world. Teach them to use Gush.', $pr['body']);
        $this->assertEquals('develop', $pr['base']['ref']);
    }

    public function testDoNotSwitchPullRequestBaseWhenBaseIsUnchanged()
    {
        $command = new PullRequestSwitchBaseCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('base_ref')->reveal());
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
            }
        );

        $tester->execute(
            ['pr_number' => 10, 'base_branch' => 'base_ref']
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Pull-request base-branch is already based on base_ref!',
            $display
        );
    }

    private function getLocalGitHelper($baseBranch = 'develop')
    {
        $helper = $this->getGitHelper();

        if ('base_ref' !== $baseBranch) {
            $helper->remoteUpdate('cordoval')->shouldBeCalled();
            $helper->remoteUpdate('gushphp')->shouldBeCalled();
            $helper->switchBranchBase(
                'head_ref',
                'gushphp/base_ref',
                'gushphp/'.$baseBranch,
                'head_ref-switched'
            )->shouldBeCalled();

            $helper->pushToRemote('cordoval', 'head_ref-switched', true)->shouldBeCalled();
            $helper->pushToRemote('cordoval', ':head_ref')->shouldBeCalled();
        }

        return $helper;
    }

    protected function getGitConfigHelper($expected = true)
    {
        $helper = parent::getGitConfigHelper();

        if ($expected) {
            $helper->ensureRemoteExists('cordoval', 'gush')->shouldBeCalled();
            $helper->ensureRemoteExists('gushphp', 'gush')->shouldBeCalled();
        }

        return $helper;
    }
}
