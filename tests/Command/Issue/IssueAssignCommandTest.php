<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Issue;

use Gush\Command\Issue\IssueAssignCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;

class IssueAssignCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester($command = new IssueAssignCommand());
        $tester->execute(
            [
                '--org' => 'gushphp',
                '--repo' => 'gush',
                'issue_number' => TestAdapter::ISSUE_NUMBER,
                'username' => 'cordoval',
            ],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf(
                'Issue https://github.com/gushphp/gush/issues/%s was assigned to cordoval!',
                TestAdapter::ISSUE_NUMBER
            ),
            trim($tester->getDisplay(true))
        );
    }
}
