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

use Gush\Command\IssueShowCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueShowCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/issues/60')->thenReturn(
            [
                'number' => 60,
                'state' => "open",
                'user' => ['login' => 'weaverryan'],
                'assignee' => ['login' => 'cordoval'],
                'pull_request' => [],
                'milestone' => ['title' => "Conquer the world"],
                'labels' => [['name' => 'actionable'], ['name' => 'easy pick']],
                'title' => 'Write a behat test to launch strategy',
                'body' => 'Help me conquer the world. Teach them to use gush.',
            ]
        );

        $tester = $this->getCommandTester(new IssueShowCommand());
        $tester->execute(['issue_number' => 60, '--org' => 'cordoval', '--repo' => 'gush']);

        $this->assertEquals(trim(OutputFixtures::ISSUE_SHOW), trim($tester->getDisplay()));
    }
}
