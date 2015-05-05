<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueShowCommand;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class IssueShowCommandTest extends CommandTestCase
{
    public function testShowsIssue()
    {
        $tester = $this->getCommandTester(new IssueShowCommand());
        $tester->execute(['issue' => 60]);

        $this->assertCommandOutputMatches(
            [
                'Issue #60 (open): by weaverryan [cordoval]',
                'Milestone: v1.0',
                'Labels: actionable, easy pick',
                'Title: Write a behat test to launch strategy',
                'Link: https://github.com/gushphp/gush/issues/60',
                'Help me conquer the world. Teach them to use Gush.'
            ],
            $tester->getDisplay()
        );
    }

    public function testShowsIssueWithNumberFromBranchName()
    {
        $command = new IssueShowCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            null,
            function (HelperSet $helperSet) {
                $helperSet->set($this->getLocalGitHelper('test_branch')->reveal());
            }
        );

        $tester->execute();

        $this->assertCommandOutputMatches(
            [
                'Issue #60 (open): by weaverryan [cordoval]',
                'Milestone: v1.0',
                'Labels: actionable, easy pick',
                'Title: Write a behat test to launch strategy',
                'Link: https://github.com/gushphp/gush/issues/60',
                'Help me conquer the world. Teach them to use Gush.'
            ],
            $tester->getDisplay()
        );
    }

    private function getLocalGitHelper()
    {
        $helper = $this->getGitHelper(true);
        $helper->getIssueNumber()->willReturn(60);

        return $helper;
    }
}
