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

use Gush\Command\PullRequestVersionEyeCommand;
use Gush\Tests\Fixtures\OutputFixtures;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestVersionEyeCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $processHelper = $this->expectProcessHelper();
        $tester = $this->getCommandTester($command = new PullRequestVersionEyeCommand());
        $command->getHelperSet()->set($processHelper, 'process');
        $tester->execute(['--org' => 'gushphp', '--repo' => 'gush-sandbox'], ['interactive' => false]);

        $res = trim($tester->getDisplay());
        $this->assertEquals(OutputFixtures::PULL_REQUEST_VERSIONEYE, $res);
    }

    private function expectProcessHelper()
    {
        $processHelper = $this->getMock(
            'Gush\Helper\ProcessHelper',
            ['runCommands']
        );
        $processHelper->expects($this->once())
            ->method('setOutput')
            ->with(new BufferedOutput())
        ;
        $processHelper->expects($this->once())
            ->method('runCommands')
            ->with(
                [
                    [
                        'line' => 'composer require symfony/console 2.4.2 --no-update',
                        'allow_failures' => true,
                    ],
                ]
            )
        ;

        return $processHelper;
    }
}
