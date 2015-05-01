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

use Gush\Command\Branch\BranchForkCommand;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class BranchForkCommandTest extends CommandTestCase
{
    public function testForkRepositoryToUserOrg()
    {
        $command = new BranchForkCommand();
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
            [
                'Forked repository gushphp/gush into cordoval/gush',
                'Added remote "cordoval" with "git@github.com:cordoval/gush.git".',
            ],
            $display
        );
    }

    public function testForkRepositoryTosSpecificOrg()
    {
        $command = new BranchForkCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper('someone')->reveal());
            }
        );

        $tester->execute(['target_organization' => 'someone']);

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Forked repository gushphp/gush into someone/gush',
                'Added remote "someone" with "git@github.com:cordoval/gush.git".',
            ],
            $display
        );
    }

    protected function getGitConfigHelper($remoteName = 'cordoval')
    {
        $gitHelper = parent::getGitConfigHelper();
        $gitHelper->setRemote($remoteName, 'git@github.com:cordoval/gush.git')->shouldBeCalled();

        return $gitHelper;
    }
}
