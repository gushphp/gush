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

use Gush\Command\IssueMilestoneListCommand;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class IssueMilestoneListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $params = [
            'page' => 1,
            'state' => 'open',
            'sort' => 'due_date',
            'direction' => 'desc'
        ];

        $this->httpClient->whenGet('repos/cordoval/gush/milestones', $params)->thenReturn(
            [
                [
                    'title' => 'version 1.0'
                ],
            ]
        );

        $tester = $this->getCommandTester(new IssueMilestoneListCommand());
        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush']);

        $this->assertEquals("version 1.0", trim($tester->getDisplay()));
    }
}
