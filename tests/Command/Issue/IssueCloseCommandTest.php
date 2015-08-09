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

use Gush\Command\Issue\IssueCloseCommand;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestAdapter;

class IssueCloseCommandTest extends CommandTestCase
{
    public function testCloseIssue()
    {
        $tester = $this->getCommandTester(new IssueCloseCommand());
        $tester->execute(
            [
                'issue_number' => TestAdapter::ISSUE_NUMBER,
            ]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            sprintf(
                'Closed https://github.com/gushphp/gush/issues/%s',
                TestAdapter::ISSUE_NUMBER
            ),
            $display
        );
    }
}
