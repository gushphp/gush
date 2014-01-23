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

use Gush\Command\ConfigureCommand;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ConfigureCommandTest extends BaseTestCase
{
    const PASSWORD = 'foo';
    const USERNAME = 'bar';

    public function testCommand()
    {
        if (!$homeDir = getenv('GUSH_HOME')) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $gushFilename = $homeDir.'/.gush.yml';

        $expected = [
            'parameters' => [
                'cache-dir' => $homeDir.'/cache',
                'home' => $homeDir,
                'github' => ['username' => self::USERNAME, 'password' => self::PASSWORD]
            ]
        ];

        @mkdir($homeDir, 0777, true);

        $this->httpClient->whenGet('authorizations')->thenReturn([]);

        $dialog = $this->expectDialogParameters($homeDir);

        $tester = $this->getCommandTester($command = new ConfigureCommand());
        $command->getHelperSet()->set($dialog, 'dialog');
        $tester->execute(['command' => 'configure']);

        $this->assertFileExists($gushFilename);

        $this->assertEquals($expected, Yaml::parse($gushFilename));
    }

    private function expectDialogParameters($homeDir)
    {
        $dialog = $this->getMock(
            'Symfony\Component\Console\Helper\DialogHelper',
            ['askAndValidate', 'askHiddenResponseAndValidate']
        );
        $dialog->expects($this->at(0))
            ->method('askAndValidate')
            ->will($this->returnValue(self::USERNAME));
        $dialog->expects($this->at(1))
            ->method('askHiddenResponseAndValidate')
            ->will($this->returnValue(self::PASSWORD));
        $dialog->expects($this->at(2))
            ->method('askAndValidate')
            ->will($this->returnValue($homeDir.'/cache'));

        return $dialog;
    }
}
