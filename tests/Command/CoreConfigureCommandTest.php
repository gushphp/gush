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
use Gush\Tester\QuestionToken;
use Prophecy\Argument;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * @group now
 */
class CoreConfigureCommandTest extends BaseTestCase
{
    const PASSWORD = 'foo';
    const TOKEN = 'foo';
    const USERNAME = 'bar';
    const VERSIONEYE_TOKEN = 'token';

    const ADAPTER_ONLY = 1;
    const ADAPTER_AND_TRACKER = 2;
    const TRACKER_ONLY = 3;
    const NEITHER_ADAPTER_NOR_TRACKER = 4;

    public function testCommandWithoutOptions()
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
                    ],
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

        $questionHelper = $this->expectDialogParameters($homeDir, self::NEITHER_ADAPTER_NOR_TRACKER);
        $tester = $this->getCommandTester($command = new CoreConfigureCommand());
        $command->getHelperSet()->set($questionHelper, 'question');
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

    public function testCommandWithJustAdapter()
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
                'issue_trackers' => [],
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

        $questionHelper = $this->expectDialogParameters($homeDir, self::ADAPTER_ONLY);
        $tester = $this->getCommandTester($command = new CoreConfigureCommand());
        $command->getHelperSet()->set($questionHelper, 'question');
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

    private function expectDialogParameters($homeDir, $option)
    {
        $questionHelper = $this->prophet->prophesize('Symfony\Component\Console\Helper\QuestionHelper');

        $questionHelper->getName()->willReturn('question');
        $questionHelper->setHelperSet(Argument::any())->shouldBeCalled();

        if (self::NEITHER_ADAPTER_NOR_TRACKER === $option) {
            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new ChoiceQuestion(
                        'Choose adapter: ',
                        ['github', 'github_enterprise']
                    )
                )
            )->willReturn('github_enterprise');
        }

        if (self::NEITHER_ADAPTER_NOR_TRACKER === $option) {
            // AdapterConfigurator Start
            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new ChoiceQuestion(
                        'Choose GitHub Enterprise authentication type:',
                        ['Password', 'Token'],
                        'Password'
                    )
                )
            )->willReturn('Password');

            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new Question('Username:')
                )
            )->willReturn(self::USERNAME);

            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new Question('Password:')
                )
            )->willReturn(self::PASSWORD);

            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new Question('Enter your GitHub Enterprise api url []: ', '')
                )
            )->willReturn('https://company.com/api/v3/');

            $questionHelper->ask(
                Argument::type('Symfony\Component\Console\Input\InputInterface'),
                Argument::type('Symfony\Component\Console\Output\OutputInterface'),
                new QuestionToken(
                    new Question('Enter your GitHub Enterprise repo url []: ', '')
                )
            )->willReturn('https://company.com');
            // AdapterConfigurator End
        }

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new ConfirmationQuestion(
                    'Would you like to make "github_enterprise" the default adapter?'
                )
            )
        )->willReturn(true);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new ChoiceQuestion(
                    'Choose issue tracker:',
                    ['github', 'jira']
                )
            )
        )->willReturn('jira');

        // IssueTrackerConfigurator Start
        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new ChoiceQuestion(
                    'Choose Jira authentication type:',
                    ['Password', 'Token'],
                    'Password'
                )
            )
        )->willReturn('Token');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new Question('Token:')
            )
        )->willReturn(self::TOKEN);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new Question('Enter your Jira api url []:', '')
            )
        )->willReturn('https://jira.company.com/api/v2/');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new Question('Enter your Jira repo url []:', '')
            )
        )->willReturn('https://jira.company.com/');
        // IssueTrackerConfigurator End

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new ConfirmationQuestion(
                    'Would you like to make "jira" the default issue tracker?'
                )
            )
        )->willReturn(true);

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new Question('Cache folder', $homeDir.'/cache')
            )
        )->willReturn($homeDir.'/cache');

        $questionHelper->ask(
            Argument::type('Symfony\Component\Console\Input\InputInterface'),
            Argument::type('Symfony\Component\Console\Output\OutputInterface'),
            new QuestionToken(
                new Question('VersionEye token:', 'NO_TOKEN')
            )
        )->willReturn(self::VERSIONEYE_TOKEN);

        return $questionHelper->reveal();
    }
}
