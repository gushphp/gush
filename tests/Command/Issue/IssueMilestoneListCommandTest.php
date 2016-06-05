<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueMilestoneListCommand;
use Gush\Tests\Command\CommandTestCase;

class IssueMilestoneListCommandTest extends CommandTestCase
{
    public function testListsMilestones()
    {
        $tester = $this->getCommandTester(new IssueMilestoneListCommand());
        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches('Issue milestones on gushphp/gush', $display);
        $this->assertCommandOutputMatches('version 1.0', $display);
    }
}
