<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchMergeCommand;
use Gush\Operation\RemoteMergeOperation;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class BranchMergeCommandTest extends CommandTestCase
{
    const MERGE_HASH = '8ae59958a2632018275b8db9590e9a79331030cb';

    const COMMAND_DISPLAY = 'Branch "%s" has been merged into "%s"';

    private $mergeMessage = "Merge branch '%s' into %s";

    public function testMergeBranchWithDefaultWorkFlow()
    {
        $command = new BranchMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'develop', 'master'))->reveal());
            }
        );

        $tester->execute(
            ['target_branch' => 'master', 'source_branch' => 'develop'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(
            sprintf(self::COMMAND_DISPLAY, 'gushphp/develop', 'gushphp/master'),
            $display
        );
    }

    public function testMergeBranchWithConfiguredWorkflow()
    {
        $command = new BranchMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            array_merge(
                self::$localConfig,
                [
                    'merge_workflow' => ['validation' => ['preset' => 'git-flow']]
                ]
            ),
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper(sprintf($this->mergeMessage, 'develop', 'master'))->reveal());
            }
        );

        $tester->execute(
            ['target_branch' => 'master', 'source_branch' => 'develop'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(
            sprintf(self::COMMAND_DISPLAY, 'gushphp/develop', 'gushphp/master'),
            $display
        );
    }

    public function testMergeBranchWithCustomMessage()
    {
        $command = new BranchMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('Merge upstream changes into master')->reveal());
            }
        );

        $tester->execute(
            [
                'target_branch' => 'master',
                'source_branch' => 'develop',
                '--message' => 'Merge upstream changes into master',
            ],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(
            sprintf(self::COMMAND_DISPLAY, 'gushphp/develop', 'gushphp/master'),
            $display
        );
    }

    public function testMergePullRequestWithSquashOption()
    {
        $command = new BranchMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(sprintf($this->mergeMessage, 'develop', 'master'), true)->reveal()
                );
            }
        );

        $tester->execute(
            ['target_branch' => 'master', 'source_branch' => 'develop', '--squash' => true],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(
            sprintf(self::COMMAND_DISPLAY, 'gushphp/develop', 'gushphp/master'),
            $display
        );
    }

    public function testMergePullRequestWithForceSquashOption()
    {
        $command = new BranchMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(sprintf($this->mergeMessage, 'develop', 'master'), true, true)->reveal()
                );
            }
        );

        $tester->execute(
            ['target_branch' => 'master', 'source_branch' => 'develop', '--force-squash' => true],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(
            sprintf(self::COMMAND_DISPLAY, 'gushphp/develop', 'gushphp/master'),
            $display
        );
    }

    public function testMergePullRequestWithFastForward()
    {
        $command = new BranchMergeCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(
                        sprintf($this->mergeMessage, 'develop', 'master'),
                        false,
                        false,
                        true
                    )->reveal()
                );
            }
        );

        $tester->execute(
            ['target_branch' => 'master', 'source_branch' => 'develop', '--fast-forward' => true],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();
        $this->assertCommandOutputMatches(
            sprintf(self::COMMAND_DISPLAY, 'gushphp/develop', 'gushphp/master'),
            $display
        );
    }

    protected function getGitConfigHelper()
    {
        $helper = parent::getGitConfigHelper();

        $helper->ensureRemoteExists('gushphp', 'gush')->shouldBeCalled();

        return $helper;
    }

    private function getLocalGitHelper($message = null, $squash = false, $forceSquash = false, $fastForward = false)
    {
        $helper = parent::getGitHelper();

        $mergeOperation = $this->prophesize(RemoteMergeOperation::class);
        $mergeOperation->setTarget('gushphp', 'master')->shouldBeCalled();
        $mergeOperation->setSource('gushphp', 'develop')->shouldBeCalled();
        $mergeOperation->squashCommits($squash, $forceSquash)->shouldBeCalled();
        $mergeOperation->useFastForward($fastForward)->shouldBeCalled();
        $mergeOperation->setMergeMessage($message, true)->shouldBeCalled();

        $mergeOperation->performMerge()->willReturn(self::MERGE_HASH);
        $mergeOperation->pushToRemote()->shouldBeCalled();

        $helper->createRemoteMergeOperation()->willReturn($mergeOperation->reveal());

        return $helper;
    }
}
