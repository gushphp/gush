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

use Gush\Command\Issue\IssueAssignCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;

class IssueAssignCommandTest extends CommandTestCase
{
    public function testAssignsIssue()
    {
        $tester = $this->getCommandTester(new IssueAssignCommand());
        $tester->execute(
            [
                'issue_number' => TestAdapter::ISSUE_NUMBER,
                'username' => 'cordoval',
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            sprintf(
                'Issue https://github.com/gushphp/gush/issues/%s is now assigned to cordoval!',
                TestAdapter::ISSUE_NUMBER
            ),
            $display
        );
    }
}
