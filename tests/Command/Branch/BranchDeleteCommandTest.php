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

use Gush\Command\Branch\BranchDeleteCommand;
use Gush\Exception\UserException;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\OutputFixtures;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;

class BranchDeleteCommandTest extends CommandTestCase
{
    public function testDeletesCurrentRemoteBranchWhenNonInteractive()
    {
        $command = new BranchDeleteCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('test_branch')->reveal());
            }
        );

        $tester->execute(['--org' => 'my-org'], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch cordoval/test_branch has been deleted!',
            $display
        );
    }

    public function testDeletesSpecificRemoteBranchWhenNonInteractive()
    {
        $command = new BranchDeleteCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('test_branch', 'foo', true, 'cordoval-forks')->reveal());
            }
        );

        $tester->execute(['organization' => 'cordoval-forks', 'branch_name' => 'foo'], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch cordoval-forks/foo has been deleted!',
            $display
        );
    }

    public function testDeletesRemoteBranchAfterConfirmationWhenInteractive()
    {
        $command = new BranchDeleteCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('test_branch', 'foo')->reveal());
            }
        );

        $this->setExpectedCommandInput($command, "yes\n");
        $tester->execute(['organization' => 'cordoval', 'branch_name' => 'foo']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch cordoval/foo has been deleted!',
            $display
        );
    }

    public function testKeepsRemoteBranchAfterCancellationWhenInteractive()
    {
        $command = new BranchDeleteCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('test_branch', null, false)->reveal());
            }
        );

        $this->setExpectedCommandInput($command, "\n"); // default is no

        $this->setExpectedException(UserException::class, 'User aborted.');

        $tester->execute();
    }

    private function getLocalGitHelper(
        $branchName = 'branch-name',
        $deletedBranch = null,
        $pushed = true,
        $remote = 'cordoval'
    ) {
        if (!$deletedBranch) {
            $deletedBranch = $branchName;
        }

        $helper = $this->getGitHelper(true);
        $helper->getActiveBranchName()->willReturn($branchName);

        if ($pushed) {
            $helper->pushToRemote($remote, ':'.$deletedBranch, true)->shouldBeCalled();
        } else {
            $helper->pushToRemote($remote, ':'.$deletedBranch, true)->shouldNotBeCalled();
        }

        return $helper;
    }
}
