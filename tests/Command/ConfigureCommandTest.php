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
        $command->getHelperSet()->set($dialog, 'question');
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
        $questionHelper = $this->prophet->prophesize('Symfony\Component\Console\Helper\QuestionHelper');

        $questionHelper->getName()->willReturn('question');
        $questionHelper->setHelperSet(Argument::any())->shouldBeCalled();

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\ChoiceQuestion')
        )->willReturn(1);

        // AdapterConfigurator Start
        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\ChoiceQuestion')
        )->willReturn(0);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn(self::USERNAME);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn(self::PASSWORD);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn('https://company.com/api/v3/');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn('https://company.com');
        // AdapterConfigurator End

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\ConfirmationQuestion')
        )->willReturn(true);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\ChoiceQuestion')
        )->willReturn(1);

        // IssueTrackerConfigurator Start
        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\ChoiceQuestion')
        )->willReturn(1);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn(self::TOKEN);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn('https://jira.company.com/api/v2/');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn('https://jira.company.com/');
        // IssueTrackerConfigurator End

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\ConfirmationQuestion')
        )->willReturn(true);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn($homeDir.'/cache');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            Argument::type('Symfony\Component\Console\Question\Question')
        )->willReturn(self::VERSIONEYE_TOKEN);

        return $questionHelper->reveal();
    }
}
