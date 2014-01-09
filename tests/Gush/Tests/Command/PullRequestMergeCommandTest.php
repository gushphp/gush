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
        $tester->execute(['org' => 'cordoval', 'prNumber' => 40]);

        $this->assertEquals("Pull Request successfully merged.", trim($tester->getDisplay()));
    }

    protected function expectShowPullRequest()
    {
        $this->httpClient->whenGet(
            'repos/cordoval/gush/pulls/40/merge',
            json_encode(['commit_message' => 'Merged using Gush'])
        )->thenReturn(
            [
                'merged' => true,
                'message' => 'Pull Request successfully merged.',
            ]
        );
    }

    protected function expectPullRequestCommits()
    {
        $this->httpClient->whenPut(
            'repos/cordoval/gush/pulls/40/merge',
            json_encode(['commit_message' => 'Merged using Gush'])
        )->thenReturn(
            [
                'merged' => true,
                'message' => 'Pull Request successfully merged.',
            ]
        );
    }

    protected function expectPullRequestMerge()
    {
        $this->httpClient->whenPut(
            'repos/cordoval/gush/pulls/40/merge',
            json_encode(['commit_message' => 'Merged using Gush'])
        )->thenReturn(
            [
                'merged' => true,
                'message' => 'Pull Request successfully merged.',
            ]
        );
    }
}
