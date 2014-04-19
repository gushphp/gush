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
use Gush\Tester\Adapter\TestAdapter;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueTakeCommandTest extends BaseTestCase
{
    const SLUGIFIED_STRING = 'write-a-behat-test-to-launch-strategy';
    const TEST_TITLE = 'Write a behat test to launch strategy';

    public function testCommand()
    {
        $text = $this->expectTextHelper();
        $process = $this->expectProcessHelper();
        $tester = $this->getCommandTester($command = new IssueTakeCommand());
        $command->getHelperSet()->set($text, 'text');
        $command->getHelperSet()->set($process, 'process');
        $tester->execute(
            ['--org' => 'gushphp', '--repo' => 'gush', 'issue_number' => TestAdapter::ISSUE_NUMBER],
            ['interactive' => false]
        );

        $this->assertEquals(
            sprintf('Issue https://github.com/gushphp/gush/issues/%s taken!', TestAdapter::ISSUE_NUMBER),
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
                    TestAdapter::ISSUE_NUMBER,
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
                        'allow_failures' => true,
                    ],
                    [
                        'line' => sprintf('git checkout origin/master'),
                        'allow_failures' => true,
                    ],
                    [
                        'line' => sprintf('git checkout -b %s', self::SLUGIFIED_STRING),
                        'allow_failures' => true,
                    ],
                ]
            )
        ;

        return $process;
    }
}
