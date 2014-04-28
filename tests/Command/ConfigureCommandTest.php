<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command;

use Github\Client;
use Gush\Command\CoreConfigureCommand;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Daniel Gomes <me@danielcsgomes.com>
 * @author Luis Cordova <cordoval@gmail.com>
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
                'home_config' => $homeDir.'/.gush/.gush.yml',
                'local' => $homeDir.'/gush',
                'local_config' => $homeDir.'/gush/.gush.yml',
                'versioneye-token' => self::VERSIONEYE_TOKEN,
                'adapters' => [
                    'github_enterprise' => [
                        'authentication' => [
                            'username' => self::USERNAME,
                            'password-or-token' => self::PASSWORD,
                            'http-auth-type' => Client::AUTH_HTTP_PASSWORD,
                        ],
                        'adapter_class' => 'Gush\Adapter\GitHubEnterpriseAdapter',
                        'config' => [
                            'base_url' => 'https://company.com/api/v3/',
                            'repo_domain_url' => 'https://company.com',
                        ],
                    ],
                ],
            ]
        ];

        @mkdir($homeDir, 0777, true);

        $dialog = $this->expectDialogParameters($homeDir);
        $tester = $this->getCommandTester($command = new CoreConfigureCommand());
        $command->getHelperSet()->set($dialog, 'dialog');
        $tester->execute(
            [
                'command' => 'core:configure',
            ],
            [
                'interactive' => true,
            ]
        );

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
            ->will($this->returnValue(1))
        ;
        $dialog->expects($this->at(1))
            ->method('select')
            ->will($this->returnValue(0))
        ;
        $dialog->expects($this->at(2))
            ->method('askAndValidate')
            ->will($this->returnValue(self::USERNAME))
        ;
        $dialog->expects($this->at(3))
            ->method('askHiddenResponseAndValidate')
            ->will($this->returnValue(self::PASSWORD))
        ;
        $dialog->expects($this->at(4))
            ->method('askAndValidate')
            ->will($this->returnValue('https://company.com/api/v3/'))
        ;
        $dialog->expects($this->at(5))
            ->method('askAndValidate')
            ->will($this->returnValue('https://company.com'))
        ;
        $dialog->expects($this->at(6))
            ->method('askAndValidate')
            ->will($this->returnValue($homeDir.'/cache'))
        ;
        $dialog->expects($this->at(7))
            ->method('askAndValidate')
            ->will($this->returnValue(self::VERSIONEYE_TOKEN))
        ;

        return $dialog;
    }
}
