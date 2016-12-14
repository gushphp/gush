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

use Gush\Command\PullRequest\PullRequestShowCommand;
use Gush\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Helper\HelperSet;

class PullRequestShowCommandTest extends CommandTestCase
{
    /**
     * @test
     */
    public function it_shows_issue()
    {
        $tester = $this->getCommandTester($command = new PullRequestShowCommand());
        $tester->execute(
            [
                'id' => 10
            ]
        );

        $this->assertCommandOutputMatches(
            [
                'Pull Request #10 - Write a behat test to launch strategy by weaverryan [open]',
                'Org/Repo: gushphp/gush',
                'Link: https://github.com/gushphp/gush/pull/10',
                'Labels: actionable, easy pick',
                'Milestone: some_good_stuff',
                'Assignee: cordoval',
                'Source => Target: cordoval/gush#head_ref => gushphp/gush#base_ref'
            ],
            $tester->getDisplay()
        );
    }
}
