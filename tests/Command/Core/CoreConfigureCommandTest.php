<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Command\Core;

use Gush\Command\Core\CoreConfigureCommand;
use Gush\Config;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestConfigurator;

class CoreConfigureCommandTest extends CommandTestCase
{
    protected function requiresRealConfigDir()
    {
        return true;
    }

    public function testRunCommandWithNoExistingConfig()
    {
        $command = new CoreConfigureCommand();
        $tester = $this->getCommandTester($command, [], []);

        $this->assertFileNotExists($command->getConfig()->get('home_config'));

        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("1\nno\n"));

        $tester->execute(
            ['command' => $command->getName()],
            ['decorated' => false]
        );

        $display = $tester->getDisplay(true);

        $this->assertCommandOutputMatches(
            [
                "Choose adapter:\n[0] Nothing (skip selection)",
                '[1] GitHub (RepositoryManager, IssueTracker)',
                'GitHub Enterprise (RepositoryManager, IssueTracker)',
                "Do you want to configure other adapters? (yes/no) [no]:\n>\n[OK] Configuration file saved successfully.",
                ['\[OK\]\sConfiguration file saved successfully\.$', true], // ensure saving message is the last line
            ],
            $display
        );

        $expected = [
            'adapters' => [
                'github' => [
                    'authentication' => [
                        'http-auth-type' => TestConfigurator::AUTH_HTTP_TOKEN,
                        'username' => TestConfigurator::USERNAME,
                        'token' => TestConfigurator::PASSWORD,
                    ],
                    'base_url' => 'https://api.github.com/',
                    'repo_domain_url' => 'https://github.com',
                ],
            ],
        ];

        $this->assertFileExists($command->getConfig()->get('home_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_SYSTEM));
    }

    public function testRunCommandNoSelectionWithNoExistingConfig()
    {
        $command = new CoreConfigureCommand();
        $tester = $this->getCommandTester($command, [], []);

        $this->assertFileNotExists($command->getConfig()->get('home_config'));

        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("0\n"));

        $tester->execute(
            ['command' => $command->getName()],
            ['decorated' => false]
        );

        $display = $tester->getDisplay(true);

        $this->assertCommandOutputMatches(
            [
                'Choose adapter:',
                "Choose adapter:\n[0] Nothing (skip selection)",
                '[1] GitHub (RepositoryManager, IssueTracker)',
                'GitHub Enterprise (RepositoryManager, IssueTracker)',
                ['\[OK\]\sConfiguration file saved successfully\.$', true],
            ],
            $display
        );

        $this->assertFileExists($command->getConfig()->get('home_config'));
        $this->assertEquals(['adapters' => []], $command->getConfig()->toArray(Config::CONFIG_SYSTEM));
    }

    public function testRunCommandWithExistingConfig()
    {
        $command = new CoreConfigureCommand();
        $tester = $this->getCommandTester(
            $command,
            [
                'adapters' => [
                    'github' => [
                        'authentication' => [
                            'http-auth-type' => TestConfigurator::AUTH_HTTP_TOKEN,
                            'username' => TestConfigurator::USERNAME,
                            'token' => TestConfigurator::PASSWORD,
                        ],
                        'base_url' => 'https://api.github.com/',
                        'repo_domain_url' => 'https://github.com',
                    ],
                ],
            ],
            []
        );

        $this->assertFileNotExists($command->getConfig()->get('home_config'));

        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("2\nno\n"));

        $tester->execute(
            ['command' => $command->getName()],
            ['decorated' => false]
        );

        $display = $tester->getDisplay(true);

        $this->assertCommandOutputMatches(
            [
                'Choose adapter:',
                "Choose adapter:\n[0] Nothing (skip selection)",
                '[1] * GitHub (RepositoryManager, IssueTracker)',
                'GitHub Enterprise (RepositoryManager, IssueTracker)',
                "Do you want to configure other adapters? (yes/no) [no]:\n>\n[OK] Configuration file saved successfully.",
                ['\[OK\]\sConfiguration file saved successfully\.$', true],
            ],
            $display
        );

        $expected = [
            'adapters' => [
                'github' => [
                        'authentication' => [
                            'http-auth-type' => TestConfigurator::AUTH_HTTP_TOKEN,
                            'username' => TestConfigurator::USERNAME,
                            'token' => TestConfigurator::PASSWORD,
                        ],
                    'base_url' => 'https://api.github.com/',
                    'repo_domain_url' => 'https://github.com',
                ],
                'github_enterprise' => [
                        'authentication' => [
                            'http-auth-type' => TestConfigurator::AUTH_HTTP_TOKEN,
                            'username' => TestConfigurator::USERNAME,
                            'token' => TestConfigurator::PASSWORD,
                        ],
                    'base_url' => 'https://api.github.com/',
                    'repo_domain_url' => 'https://github.com',
                ],
            ],
        ];

        $this->assertFileExists($command->getConfig()->get('home_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_SYSTEM));
    }

    public function testRunCommandWithMultipleAdapters()
    {
        $command = new CoreConfigureCommand();
        $tester = $this->getCommandTester($command, [], []);

        $this->assertFileNotExists($command->getConfig()->get('home_config'));

        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("1\nyes\n2\nno"));

        $tester->execute(
            ['command' => $command->getName()],
            ['decorated' => false]
        );

        $display = $tester->getDisplay(true);

        $this->assertCommandOutputMatches(
            [
                'Choose adapter:',
                "Choose adapter:\n[0] Nothing (skip selection)",
                '[1] GitHub (RepositoryManager, IssueTracker)',
                'GitHub Enterprise (RepositoryManager, IssueTracker)',
                // ask twice
                "Do you want to configure other adapters? (yes/no) [no]:\n>\nChoose adapter:",
                ['\[OK\]\sConfiguration file saved successfully\.$', true], // ensure saving message is the last line
            ],
            $display
        );

        $expected = [
            'adapters' => [
                'github' => [
                    'authentication' => [
                        'http-auth-type' => TestConfigurator::AUTH_HTTP_TOKEN,
                        'username' => TestConfigurator::USERNAME,
                        'token' => TestConfigurator::PASSWORD,
                    ],
                    'base_url' => 'https://api.github.com/',
                    'repo_domain_url' => 'https://github.com',
                ],
                'github_enterprise' => [
                    'authentication' => [
                        'http-auth-type' => TestConfigurator::AUTH_HTTP_TOKEN,
                        'username' => TestConfigurator::USERNAME,
                        'token' => TestConfigurator::PASSWORD,
                    ],
                    'base_url' => 'https://api.github.com/',
                    'repo_domain_url' => 'https://github.com',
                ],
            ],
        ];

        $this->assertFileExists($command->getConfig()->get('home_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_SYSTEM));
    }
}
