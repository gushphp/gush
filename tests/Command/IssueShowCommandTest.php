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

use Gush\Command\IssueShowCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueShowCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new IssueShowCommand());
        $tester->execute(['issue_number' => 60, '--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(trim(OutputFixtures::ISSUE_SHOW), trim($tester->getDisplay()));
    }
}
