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
use Gush\Command\Core\CoreConfigureCommand;
use Prophecy\Argument;
use Symfony\Component\Yaml\Yaml;

class ConfigureCommandTest extends BaseTestCase
{
    const PASSWORD = 'foo';
    const TOKEN = 'foo';
    const USERNAME = 'bar';
    const VERSIONEYE_TOKEN = 'token';

    public function testCommand()
    {
        if (!$homeDir = getenv('GUSH_HOME')) {
            $this->markTestSkipped('Please add the \'GUSH_HOME\' in your \'phpunit.xml\'.');
        }

        $gushFilename = $homeDir.'/.gush.yml';
        $localDir = getcwd();
        $expected = [
            'parameters' => [
                'cache-dir' => $homeDir.'/cache',
                'adapters' => [
                    'github_enterprise' => [
                        'authentication' => [
                            'http-auth-type' => Client::AUTH_HTTP_PASSWORD,
                            'username' => self::USERNAME,
                            'password-or-token' => self::PASSWORD,
                        ],
                        'base_url' => 'https://company.com/api/v3/',
                        'repo_domain_url' => 'https://company.com',
                    ],
                ],
                'issue_trackers' => [
                    'jira' => [
                        'authentication' => [
                            'http-auth-type' => Client::AUTH_HTTP_TOKEN,
                            'username' => self::USERNAME,
                            'password-or-token' => self::TOKEN,
                        ],
                        'base_url' => 'https://jira.company.com/api/v2/',
                        'repo_domain_url' => 'https://jira.company.com/',
                    ]

                ],
                'home' => $homeDir,
                'home_config' => $homeDir.'/.gush.yml',
                'local' => $localDir,
                'local_config' => $localDir.'/.gush.yml',
                'adapter' => 'github_enterprise',
                'issue_tracker' => 'jira',
                'versioneye-token' => self::VERSIONEYE_TOKEN,
            ]
        ];

        @mkdir($homeDir, 0777, true);

        if (file_exists($gushFilename)) {
            unlink($gushFilename);
        }

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
        $dialog = $this->prophet->prophesize('Symfony\Component\Console\Helper\DialogHelper');

        $dialog->getName()->willReturn('dialog');
        $dialog->setHelperSet(Argument::any())->shouldBeCalled();

        $dialog->select(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Choose adapter:'),
            ['github', 'github_enterprise'],
            0
        )->willReturn(1);

        // AdapterConfigurator Start
        $dialog->select(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Choose GitHub Enterprise authentication type:'),
            ['Password', 'Token'],
            0
        )->willReturn(0);

        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Username:'),
            Argument::any()
        )->willReturn(self::USERNAME);

        $dialog->askHiddenResponseAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Password:'),
            Argument::any()
        )->willReturn(self::PASSWORD);

        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Enter your GitHub Enterprise api url []:'),
            Argument::any(),
            false,
            ''
        )->willReturn('https://company.com/api/v3/');

        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Enter your GitHub Enterprise repo url []:'),
            Argument::any(),
            false,
            ''
        )->willReturn('https://company.com');
        // AdapterConfigurator End

        $dialog->askConfirmation(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            'Would you like to make "github_enterprise" the default adapter?',
            true
        )->willReturn(true);


        $dialog->select(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Choose issue tracker:'),
            ['github', 'jira'],
            null
        )->willReturn(1);

        // IssueTrackerConfigurator Start
        $dialog->select(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Choose Jira authentication type:'),
            ['Password', 'Token'],
            0
        )->willReturn(1);

        $dialog->askHiddenResponseAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Token:'),
            Argument::any()
        )->willReturn(self::TOKEN);

        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Enter your Jira api url []:'),
            Argument::any(),
            false,
            ''
        )->willReturn('https://jira.company.com/api/v2/');

        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Enter your Jira repo url []:'),
            Argument::any(),
            false,
            ''
        )->willReturn('https://jira.company.com/');
        // IssueTrackerConfigurator End

        $dialog->askConfirmation(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            'Would you like to make "jira" the default issue tracker?',
            true
        )->willReturn(true);


        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('Cache folder'),
            Argument::any(),
            false,
            $homeDir.'/cache'
        )->willReturn($homeDir.'/cache');

        $dialog->askAndValidate(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::containingString('VersionEye token:'),
            Argument::any(),
            false,
            'NO_TOKEN'
        )->willReturn(self::VERSIONEYE_TOKEN);

        return $dialog->reveal();
    }
}
