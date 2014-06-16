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

use Gush\Command\Issue\IssueShowCommand;
use Gush\Tests\Fixtures\OutputFixtures;

class IssueShowCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester(new IssueShowCommand());
        $tester->execute(['--issue' => 60, '--org' => 'gushphp', '--repo' => 'gush'], ['interactive' => false]);

        $this->assertEquals(trim(OutputFixtures::ISSUE_SHOW), trim($tester->getDisplay(true)));
    }
}
