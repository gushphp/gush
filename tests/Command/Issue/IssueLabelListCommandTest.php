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

use Gush\Command\Issue\IssueLabelListCommand;
use Gush\Tests\Command\CommandTestCase;

class IssueLabelListCommandTest extends CommandTestCase
{
    public function testShowAvailableIssueLabels()
    {
        $tester = $this->getCommandTester(new IssueLabelListCommand());
        $tester->execute();

        $this->assertCommandOutputMatches(['bug', 'feature', 'documentation'], $tester->getDisplay());
    }
}
