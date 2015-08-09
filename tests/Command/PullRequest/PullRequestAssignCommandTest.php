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

use Gush\Command\PullRequest\PullRequestAssignCommand;
use Gush\Tests\Command\CommandTestCase;

class PullRequestAssignCommandTest extends CommandTestCase
{
    public function testAssignPullRequestToUser()
    {
        $tester = $this->getCommandTester($command = new PullRequestAssignCommand());
        $tester->execute(
            [
                'pr_number' => 10,
                'username' => 'cordoval',
            ]
        );

        $this->assertCommandOutputMatches(
            'Pull-request https://github.com/gushphp/gush/pull/10 is now assigned to "cordoval"!',
            $tester->getDisplay()
        );
    }
}
