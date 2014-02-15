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

use Github\Client;
use Gush\Command\ConfigureCommand;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ConfigureCommandTest extends BaseTestCase
{
    const PASSWORD = 'foo';
    const USERNAME = 'bar';
    const VERSIONEYE_TOKEN = 'token';

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
                'unknown' => [],
                'authentication' => [
                    'username' => self::USERNAME,
                    'password-or-token' => self::PASSWORD,
                    'http-auth-type' => Client::AUTH_HTTP_PASSWORD,
                ],
                'adapter_class' => 'Gush\\Tester\\Adapter\\TestAdapter',
                'versioneye-token' => self::VERSIONEYE_TOKEN,
            ]
        ];

        @mkdir($homeDir, 0777, true);

        $dialog = $this->expectDialogParameters($homeDir);

        $tester = $this->getCommandTester($command = new ConfigureCommand());
        $command->getHelperSet()->set($dialog, 'dialog');
        $tester->execute(['--adapter' => 'Gush\\Tester\\Adapter\\TestAdapter', 'command' => 'configure']);

        $this->assertFileExists($gushFilename);

        $this->assertEquals($expected, Yaml::parse($gushFilename));
    }

    private function expectDialogParameters($homeDir)
    {
        $dialog = $this->getMock(
            'Symfony\Component\Console\Helper\DialogHelper',
            ['select', 'askAndValidate', 'askHiddenResponseAndValidate']
        );
        $dialog->expects($this->at(0))
            ->method('select')
            ->will($this->returnValue(0));
        $dialog->expects($this->at(1))
            ->method('askAndValidate')
            ->will($this->returnValue(self::USERNAME));
        $dialog->expects($this->at(2))
            ->method('askHiddenResponseAndValidate')
            ->will($this->returnValue(self::PASSWORD));
        $dialog->expects($this->at(3))
            ->method('askAndValidate')
            ->will($this->returnValue($homeDir.'/cache'));
        $dialog->expects($this->at(4))
            ->method('askAndValidate')
            ->will($this->returnValue(self::VERSIONEYE_TOKEN));

        return $dialog;
    }
}
