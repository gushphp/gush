<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Branch;

use Gush\Command\Branch\BranchSyncCommand;
use Gush\Operation\GitSyncOperation;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class BranchSyncCommandTest extends CommandTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';
    const TEST_REMOTE_NAME = 'cordoval';

    /**
     * @test
     */
    public function it_synchronizes_with_remote_branch()
    {
        $command = new BranchSyncCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper()->reveal());
            }
        );

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch "'.self::TEST_BRANCH_NAME.'" has been synchronised with remote "'.self::TEST_REMOTE_NAME.'".',
            $display
        );
    }

    /**
     * @test
     */
    public function it_synchronizes_local_branch_with_specific_remote()
    {
        $command = new BranchSyncCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set(
                    $this->getLocalGitHelper(
                        self::TEST_REMOTE_NAME,
                        'development',
                        GitSyncOperation::SYNC_SMART,
                        self::TEST_REMOTE_NAME,
                        'development'
                    )->reveal()
                );
            }
        );

        $tester->execute(['source_remote' => self::TEST_REMOTE_NAME, 'source_branch' => 'development']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch "development" has been synchronised with remote "cordoval".',
            $display
        );
    }

    private function getLocalGitHelper(
        $remote = self::TEST_REMOTE_NAME,
        $branchName = self::TEST_BRANCH_NAME,
        $strategy = GitSyncOperation::SYNC_SMART,
        $destRemote = self::TEST_REMOTE_NAME,
        $destBranch = self::TEST_BRANCH_NAME,
        $options = 0
    ) {
        $tester = $this;

        $helper = $this->getGitHelper();
        $helper->getActiveBranchName()->willReturn($branchName);
        $helper->createSyncOperation()->will(
            function () use ($tester, $remote, $branchName, $strategy, $destRemote, $destBranch, $options) {
                $syncer = $tester->prophesize('Gush\Operation\GitSyncOperation');
                $syncer->setLocalRef($branchName)->shouldBeCalled();
                $syncer->setRemoteRef($remote, $branchName)->shouldBeCalled();
                $syncer->setRemoteDestination($destRemote, $destBranch)->shouldBeCalled();
                $syncer->sync($strategy, $options)->shouldBeCalled();

                return $syncer->reveal();
            }
        );

        return $helper;
    }
}
