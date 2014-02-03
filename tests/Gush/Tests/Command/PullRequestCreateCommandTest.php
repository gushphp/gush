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

use Gush\Command\PullRequestCreateCommand;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class PullRequestCreateCommandTest extends BaseTestCase
{
    public function provideCommand()
    {
        return [
            [[
                '--org' => 'cordoval',
                '--repo' => 'gush',
                '--head' => 'issue-145',
                '--template' => 'default',
                '--title' => 'Test'
            ]],
        ];
    }

    /**
     * @dataProvider provideCommand
     */
    public function testCommand($args)
    {
        $this->httpClient
            ->whenPost(
                'repos/cordoval/gush/pulls',
                '{"base":"cordoval:master","head":":issue-145","title":"Test","body":""}'
            )->thenReturn(
                ['html_url' => 'this_is_the_pull_url']
            )
        ;

        $command = new PullRequestCreateCommand();
        $tester = $this->getCommandTester($command);
        $tester->execute($args, ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertEquals('this_is_the_pull_url', $res);
    }
}
