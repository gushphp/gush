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

use Gush\Command\PullRequest\PullRequestMilestoneListCommand;
use Gush\Tests\Command\CommandTestCase;

class PullRequestMilestoneListCommandTest extends CommandTestCase
{
    public function testPullRequestsAvailableMilestones()
    {
        $tester = $this->getCommandTester(new PullRequestMilestoneListCommand());
        $tester->execute();

        $this->assertCommandOutputMatches('version 1.0', $tester->getDisplay());
    }
}
