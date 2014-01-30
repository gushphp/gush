<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\BranchSyncCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchSyncCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new BranchSyncCommand());
        $tester->execute(array('--org' => 'cordoval', '--repo' => 'gush'));

        $this->assertEquals(OutputFixtures::BRANCH_SYNC, trim($tester->getDisplay()));
    }
}
