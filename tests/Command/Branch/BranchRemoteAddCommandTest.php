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

use Gush\Command\Branch\BranchRemoteAddCommand;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class BranchRemoteAddCommandTest extends CommandTestCase
{
    public function testAddRemoteForCurrentUser()
    {
        $command = new BranchRemoteAddCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper()->reveal());
            }
        );

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Added remote "cordoval"',
            $display
        );
    }

    public function testAddRemoteForSpecificOrg()
    {
        $command = new BranchRemoteAddCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper('gushphp', 'git@github.com:gushphp/gush.git')->reveal());
            }
        );

        $tester->execute(['other_organization' => 'gushphp']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Added remote "gushphp"',
            $display
        );
    }

    public function testAddRemoteForSpecificOrgAndRepo()
    {
        $command = new BranchRemoteAddCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper('gushphp', 'git@github.com:gushphp/gush.git')->reveal());
            }
        );

        $tester->execute(['other_organization' => 'gushphp', 'other_repository' => 'gushphp']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Added remote "gushphp"',
            $display
        );
    }

    public function testAddRemoteWithSpecificRemoteName()
    {
        $command = new BranchRemoteAddCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper('origin', 'git@github.com:gushphp/gush.git')->reveal());
            }
        );

        $tester->execute(['other_organization' => 'gushphp', 'other_repository' => 'gushphp', 'remote' => 'origin']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            'Added remote "origin"',
            $display
        );
    }

    protected function getGitConfigHelper($remoteName = 'cordoval', $url = 'git@github.com:cordoval/gush.git')
    {
        $gitHelper = parent::getGitConfigHelper();
        $gitHelper->setRemote($remoteName, $url)->shouldBeCalled();

        return $gitHelper;
    }
}
