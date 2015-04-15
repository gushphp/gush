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

use Gush\Command\Issue\IssueMilestoneListCommand;
use Gush\Tests\Command\BaseTestCase;

class IssueMilestoneListCommandTest extends BaseTestCase
{
    /**
     * @test
     */
    public function lists_milestones()
    {
        $tester = $this->getCommandTester(new IssueMilestoneListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals("version 1.0", trim($tester->getDisplay(true)));
    }
}
