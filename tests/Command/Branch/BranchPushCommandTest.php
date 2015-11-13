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

use Gush\Command\Branch\BranchPushCommand;
use Gush\Helper\GitHelper;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class BranchPushCommandTest extends CommandTestCase
{
    const TEST_BRANCH = 'test_branch';

    public function testPushesBranchToFork()
    {
        $command = new BranchPushCommand();
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
            'Branch pushed to cordoval/'.self::TEST_BRANCH,
            $display
        );
    }

    public function testPushesBranchToForkAndSetUpstream()
    {
        $command = new BranchPushCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('cordoval', GitHelper::SET_UPSTREAM)->reveal());
            }
        );

        $tester->execute(['--set-upstream' => true]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch pushed to cordoval/'.self::TEST_BRANCH,
            $display
        );
    }

    public function testPushesBranchToSpecificFork()
    {
        $command = new BranchPushCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('cordoval', GitHelper::SET_UPSTREAM)->reveal());
            }
        );

        $tester->execute(['--set-upstream' => true]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch pushed to cordoval/'.self::TEST_BRANCH,
            $display
        );
    }

    public function testPushesBranchToForkWithForce()
    {
        $command = new BranchPushCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('someone', GitHelper::PUSH_FORCE)->reveal());
            }
        );

        $tester->execute(['target_organization' => 'someone', '--force' => true]);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Branch pushed to someone/'.self::TEST_BRANCH,
            $display
        );
    }

    private function getLocalGitHelper($org = 'cordoval', $options = 0)
    {
        $gitHelper = $this->getGitHelper();
        $gitHelper->getActiveBranchName()->willReturn(self::TEST_BRANCH);
        $gitHelper->pushToRemote($org, self::TEST_BRANCH, $options)->shouldBeCalled();

        return $gitHelper;
    }
}
