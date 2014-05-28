<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class PullRequestMilestoneListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new \Gush\Command\PullRequest\PullRequestMilestoneListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals("version 1.0", trim($tester->getDisplay(true)));
    }
}
