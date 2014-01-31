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
 */
class IssueTakeCommandTest extends BaseTestCase
{
    const SLUGIFIED_STRING = '17-test-string';
    const TEST_TITLE = '17 test string';
    const ISSUE_NUMBER = 7;

    public function testCommand()
    {
        $this
            ->httpClient->whenGet('repos/cordoval/gush/issues/'.self::ISSUE_NUMBER)
            ->thenReturn(['title' => self::TEST_TITLE])
        ;

        $text = $this->expectTextHelper();
        $process = $this->expectProcessHelper();
        $tester = $this->getCommandTester($command = new IssueTakeCommand());
        $command->getHelperSet()->set($text, 'text');
        $command->getHelperSet()->set($process, 'process');
        $tester->execute(['--org' => 'cordoval', '--repo' => 'gush', 'issue_number' => self::ISSUE_NUMBER]);

        $this->assertEquals(
            sprintf('Issue https://github.com/cordoval/gush/issues/%s taken!', self::ISSUE_NUMBER),
            trim($tester->getDisplay())
        );
    }

    private function expectTextHelper()
    {
        $text = $this->getMock(
            'Gush\Helper\TextHelper',
            ['slugify']
        );
        $text->expects($this->once())
            ->method('slugify')
            ->with(
                sprintf(
                    '%d %s',
                    self::ISSUE_NUMBER,
                    self::TEST_TITLE
                )
            )
            ->will($this->returnValue(self::SLUGIFIED_STRING));

        return $text;
    }

    private function expectProcessHelper()
    {
        $process = $this->getMock(
            'Gush\Helper\ProcessHelper',
            ['runCommands']
        );
        $process
            ->expects($this->once())
            ->method('runCommands')
            ->with(
                [
                    [
                        'line' => 'git remote update',
                        'allow_failures' => true
                    ],
                    [
                        'line' => 'git checkout origin/master',
                        'allow_failures' => true
                    ],
                    [
                        'line' => sprintf('git checkout -b %s', self::SLUGIFIED_STRING),
                        'allow_failures' => true
                    ],
                ]
            )
        ;

        return $process;
    }
}
