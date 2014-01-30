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

use Gush\Command\IssueListCommand;
use Gush\Tests\Fixtures\OutputFixtures;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueListCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [['--org' => 'cordoval', '--repo' => 'gush']],
            [['--org' => 'cordoval', '--repo' => 'gush', '--type' => 'issue']],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {
        $this->httpClient->whenGet('repos/cordoval/gush/issues', [
            'page' => 1, 'per_page' => 100,
        ])->thenReturn(
            [
                [
                    'number' => '1',
                    'title' => 'easy issue',
                    'body' => 'this issue is easy',
                    'labels' => [['name' => 'critic'], ['name' => 'easy pick']],
                    'state' => 'open',
                    'user' => ['login' => 'cordoval'],
                    'assignee' => ['login' => 'cordoval'],
                    'milestone' => ['title' => 'some good stuff release'],
                    'created_at' => '1969-12-31',
                ],
                [
                    'number' => '2',
                    'title' => 'hard issue',
                    'body' => 'this issue is not so easy',
                    'labels' => [['name' => 'critic']],
                    'state' => 'open',
                    'user' => ['login' => 'weaverryan'],
                    'assignee' => ['login' => 'cordoval'],
                    'milestone' => ['title' => 'some good stuff release'],
                    'created_at' => '1969-12-31',
                ],
            ]
        );

        $tester = $this->getCommandTester(new IssueListCommand());
        $tester->execute($args);

        $this->assertEquals(OutputFixtures::ISSUE_LIST, trim($tester->getDisplay()));
    }
}
