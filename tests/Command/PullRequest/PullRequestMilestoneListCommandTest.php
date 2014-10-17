<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestMilestoneListCommand;
use Gush\Tests\Command\BaseTestCase;

class PullRequestMilestoneListCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function lists_pull_requests_associated_milestones()
    {
        $tester = $this->getCommandTester(new PullRequestMilestoneListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals('version 1.0', trim($tester->getDisplay(true)));
    }
}
