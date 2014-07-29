<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\PullRequest;

use Gush\Command\PullRequest\PullRequestAssignCommand;
use Gush\Tester\Adapter\TestAdapter;
use Gush\Tests\Command\BaseTestCase;

class PullRequestAssignCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $tester = $this->getCommandTester($command = new PullRequestAssignCommand());
        $tester->execute(
            [
                '--org' => 'gushphp',
                '--repo' => 'gush',
                'pr_number' => TestAdapter::PULL_REQUEST_NUMBER,
                'username' => 'cordoval',
            ],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf(
                'Pull request https://github.com/gushphp/gush/pull/%s was assigned to cordoval!',
                TestAdapter::PULL_REQUEST_NUMBER
            ),
            trim($tester->getDisplay(true))
        );
    }
}
