<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Gush\Command\PullRequestListCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class PullRequestListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new PullRequestListCommand());
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(trim(OutputFixtures::PULL_REQUEST_LIST), trim($tester->getDisplay()));
    }
}
