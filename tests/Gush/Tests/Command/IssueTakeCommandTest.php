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

use Gush\Command\IssueTakeCommand;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @group frontline
 */
class IssueTakeCommandTest extends BaseTestCase
{
    const SLUGIFIED_STRING = '17-test-string';
    const ISSUE_NUMBER = 7;

    public function testCommand()
    {
        $this
            ->httpClient->whenGet('repos/cordoval/gush/issues/'.self::ISSUE_NUMBER
            ->thenReturn(
                [
                    'title' => '17 test string',
                ]
            )
        ;

        $text = $this->expectTextHelper();
        $git = $this->expectGitHelper();
        $tester = $this->getCommandTester($command = new IssueTakeCommand());
        $command->getHelperSet()->set($text, 'text');
        $command->getHelperSet()->set($git, 'git');
        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush', 'issue_number' => self::ISSUE_NUMBER]);

        $this->assertEquals(
            sprintf('Issue https://github.com/cordoval/gush/issues/%s taken!', $issue),
            trim($tester->getDisplay())
        );
    }

    private function expectTextHelper()
    {
        $text = $this->getMock(
            'Gush\Helper\TextHelper',
            ['askAndValidate']
        );
        $text->expects($this->once())
            ->method('askAndValidate')
            ->will($this->returnValue(self::SLUGIFIED_STRING));

        return $text;
    }

    private function expectGitHelper()
    {
        $git = $this->getMock(
            'Gush\Helper\GitHelper',
            ['runCommands']
        );
        $git
            ->expects($this->once())
            ->method('runCommands')
            ->with(
                [
                    [
                        'line' => 'git remote update',
                        'allow_failures' => true
                    ],
                    [
                        'line' => sprintf('git checkout %s/%s', 'origin', $baseBranch),
                        'allow_failures' => true
                    ],
                    [
                        'line' => sprintf('git checkout -b %s', $slugTitle),
                        'allow_failures' => true
                    ],
                ]
            )
        ;

        return $git;
    }
}
