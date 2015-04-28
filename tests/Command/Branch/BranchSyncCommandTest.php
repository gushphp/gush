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

use Gush\Command\Branch\BranchSyncCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;

class BranchSyncCommandTest extends CommandTestCase
{
    const TEST_BRANCH_NAME = 'test_branch';

    public function testSyncCurrentBranchWithRemote()
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
            'Branch "'.self::TEST_BRANCH_NAME.'" has been synced with remote "origin".',
            $display
        );
    }

    public function testSyncsSpecificRanchWithSpecificRemote()
    {
        $command = new BranchSyncCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('cordoval', 'development')->reveal());
            }
        );

        $tester->execute(['remote' => 'cordoval', 'branch_name' => 'development']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch "development" has been synced with remote "cordoval".',
            $display
        );
    }

    private function getLocalGitHelper($remote = 'origin', $branch = self::TEST_BRANCH_NAME)
    {
        $helper = $this->getGitHelper();
        $helper->getActiveBranchName()->willReturn($branch);
        $helper->syncWithRemote($remote, $branch)->shouldBeCalled();

        return $helper;
    }
}
