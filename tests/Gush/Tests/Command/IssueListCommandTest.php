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

use Gush\Command\IssueListCommand;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/issues', [
            'page' => 1, 'per_page' => 100,
        ])->thenReturn(
            [
                [
                    'number' => '1',
                    'title' => 'easy issue',
                    'body' => 'this issue is easy',
                ],
                [
                    'number' => '2',
                    'title' => 'hard issue',
                    'body' => 'this issue is not so easy',
                ],
            ]
        );

        $tester = $this->getCommandTester(new IssueListCommand());
        $tester->execute(array('org' => 'cordoval', 'repo' => 'gush'));

        $this->assertEquals('bug', trim($tester->getDisplay()));
    }
}
