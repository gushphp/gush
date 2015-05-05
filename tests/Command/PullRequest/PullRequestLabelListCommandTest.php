<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestLabelListCommand;
use Gush\Tests\Command\CommandTestCase;

class PullRequestLabelListCommandTest extends CommandTestCase
{
    public function testShowAvailablePullRequestLabels()
    {
        $tester = $this->getCommandTester(new PullRequestLabelListCommand());
        $tester->execute();

        $this->assertCommandOutputMatches(['bug', 'feature', 'documentation'], $tester->getDisplay());
    }
}
