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

use Gush\Command\Issue\IssueCloseCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;
use Gush\Tests\Fixtures\OutputFixtures;

class IssueCloseCommandTest extends CommandTestCase
{
    /**
     * @test
     */
    public function closes_an_issue()
    {
        $tester = $this->getCommandTester(new IssueCloseCommand());
        $tester->execute(['--org' => 'gushphp', 'issue_number' => TestAdapter::ISSUE_NUMBER], ['interactive' => false]);

        $this->assertEquals(OutputFixtures::ISSUE_CLOSE, trim($tester->getDisplay(true)));
    }
}
