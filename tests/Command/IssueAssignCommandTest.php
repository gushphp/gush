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

use Gush\Command\IssueAssignCommand;
use Gush\Tester\Adapter\TestAdapter;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
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
            ]
        );

        $this->assertEquals(
            sprintf('Issue https://github.com/gushphp/gush/issues/%s was assigned to cordoval!', TestAdapter::ISSUE_NUMBER),
            trim($tester->getDisplay())
        );
    }
}
