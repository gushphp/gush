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

use Gush\Command\PullRequestMergeCommand;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestMergeCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->expectShowPullRequest();
        $this->expectPullRequestCommits();
        $this->expectPullRequestMerge();

        $tester = $this->getCommandTester(new PullRequestMergeCommand());
        $tester->execute(['org' => 'cordoval', 'pr_number' => 40]);

        $this->assertEquals("Pull Request successfully merged.", trim($tester->getDisplay()));
    }

    protected function expectShowPullRequest()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/pulls/40')
            ->thenReturn(
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
                    'base' => ['label' => 'master']
                ]
            )
        ;
    }

    protected function expectPullRequestCommits()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/pulls/40/commits')->thenReturn(
            [
                [
                    'sha' => '32fe234332fe234332fe234332fe234332fe2343',
                    'commit' => ['message' => 'added merge pull request feature'],
                    'author' => ['login' => 'cordoval']
                ],
                [
                    'sha' => 'ab34567812345678123456781234567812345678',
                    'commit' => ['message' => 'added final touches'],
                    'author' => ['login' => 'cordoval']
                ],
            ]
        );
    }

    protected function expectPullRequestMerge()
    {
        $this->httpClient->whenPut(
            'repos/cordoval/gush/pulls/40/merge',
            json_encode(
                [
                    'commit_message' => "This PR was merged into master branch.\n\nDiscussion\n----------\n\n"
                                    ."Write a behat test to launch strategy\n\nHelp me conquer the world. "
                                    ."Teach them to use gush.\n\nCommits\n-------\n\n"
                                    ."32fe234332fe234332fe234332fe234332fe2343 added merge pull request feature "
                                    ."cordoval\nab34567812345678123456781234567812345678 added final touches "
                                    ."cordoval\n"
                ]
            )
        )->thenReturn(
            [
                'merged' => true,
                'message' => 'Pull Request successfully merged.',
            ]
        );
    }
}
