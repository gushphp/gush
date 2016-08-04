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

use Gush\Command\Core\InitCommand;
use Gush\Config;
use Gush\Tests\Command\CommandTestCase;
use Gush\Tests\Fixtures\Adapter\TestConfigurator;
use Symfony\Component\Console\Helper\HelperSet;

class CoreInitCommandTest extends CommandTestCase
{
    protected function requiresRealConfigDir()
    {
        return true;
    }

    protected function getGitConfigHelper($hasRemote = true)
    {
        $helper = parent::getGitConfigHelper();
        $helper->getRemoteInfo('origin')->willReturn(
            [
                'host' => 'github.com',
                'vendor' => 'gushphp',
                'repo' => 'gush',
            ]
        );

        $helper->remoteExists('cordoval')->willReturn(false);
        $helper->remoteExists('origin')->willReturn($hasRemote);
        $helper->getGitConfig('remote.origin.url')->willReturn('git@github.com:gushphp/gush.git');

        return $helper;
    }

    public function testRunCommandWithAutoDetectedOptionsFromGitRemote()
    {
        $command = new InitCommand();
        $tester = $this->getCommandTester(
            $command,
            null,
            []
        );

        $this->assertFileNotExists($command->getConfig()->get('local_config'));

        // adapter, issue-tracker, org, repo, issue-org, issue-project
        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("\n\n\n\n\n\n"));

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Choose repository-manager',
                '[0] GitHub',
                '[1] GitHub Enterprise',
                'Choose issue-tracker',
                'Specify the repository organization name',
                'Specify the repository name',
                'Specify the issue-tracker organization name',
                'Specify the issue-tracker repository/project name',
                ['\[OK\]\sConfiguration file saved successfully\.$', true], // ensure saving message is the last line
            ],
            $display
        );

        $expected = [
            'repo_adapter' => 'github_enterprise',
            'issue_tracker' => 'github_enterprise',
            'repo_org' => 'gushphp',
            'repo_name' => 'gush',
            'issue_project_org' => 'gushphp',
            'issue_project_name' => 'gush',
        ];

        $this->assertFileExists($command->getConfig()->get('local_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_LOCAL));
    }

    public function testRunCommandWithoutExistingLocalConfigAndNoRemote()
    {
        $command = new InitCommand();
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
            [],
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
            }
        );

        $this->assertFileNotExists($command->getConfig()->get('local_config'));

        // adapter, issue-tracker, org, repo, issue-org, issue-project
        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("0\n0\nMyOrg\nMyRepo\nIOrg\nIRepo\n"));

        $tester->execute();

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Choose repository-manager',
                '[0] GitHub',
                '[1] GitHub Enterprise',
                'Choose issue-tracker',
                'Specify the repository organization name',
                'Specify the repository name',
                'Specify the issue-tracker organization name',
                'Specify the issue-tracker repository/project name',
                ['\[OK\]\sConfiguration file saved successfully\.$', true], // ensure saving message is the last line
            ],
            $display
        );

        $expected = [
            'repo_adapter' => 'github',
            'issue_tracker' => 'github',
            'repo_org' => 'MyOrg',
            'repo_name' => 'MyRepo',
            'issue_project_org' => 'IOrg',
            'issue_project_name' => 'IRepo',
        ];

        $this->assertFileExists($command->getConfig()->get('local_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_LOCAL));
    }

    /**
     * @dataProvider provideEmptyValues
     *
     * @param $expected
     * @param $input
     */
    public function testRunCommandWithEmptyValuesAreProhibited($expected, $input)
    {
        $command = new InitCommand();
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
            [],
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
            }
        );

        $this->assertFileNotExists($command->getConfig()->get('local_config'));

        // adapter, issue-tracker, [input]
        $this->setExpectedCommandInput($command, "0\n0\n".$input);

        $tester->execute();
        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Choose repository-manager',
                '[0] GitHub',
                '[1] GitHub Enterprise',
                'Choose issue-tracker',
                $expected,
                'Value cannot be empty.',
            ],
            $display
        );
    }

    public function provideEmptyValues()
    {
        // org, repo, issue-org, issue-project
        // "MyOrg\nMyRepo\nIOrg\nIRepo\n"

        return [
            ['Specify the repository organization name', "\nMyOrg\nMyRepo\nIOrg\nIRepo\n"],
            ['Specify the repository name', "MyOrg\n\nMyRepo\nIOrg\nIRepo\n"],

            // Not tested as these values are provided as defaults, keep this comment for clarity.
            //['Specify the issue-tracker organization name', "MyOrg\nMyRepo\n\nIOrg\nIRepo\n"],
            //['Specify the issue-tracker repository/project name', "MyOrg\nMyRepo\nIOrg\n\nIRepo\n"],
        ];
    }

    public function testRunCommandWithNoConfiguredAdapter()
    {
        $command = new InitCommand();
        $tester = $this->getCommandTester(
            $command,
            [],
            []
        );

        // adapter, issue-tracker, org, repo, issue-org, issue-project, confirm configure, select 0 (nothing)
        $helper = $command->getHelper('gush_question');
        $helper->setInputStream($this->getInputStream("0\n0\nMyOrg\nMyRepo\nIOrg\nIRepo\nyes\n0\n"));

        $tester->execute();

        $display = $tester->getDisplay(true);

        $this->assertCommandOutputMatches(
            [
                'Choose repository-manager',
                '[0] GitHub',
                '[1] GitHub Enterprise',
                'Choose issue-tracker',
                'Specify the repository organization name',
                'Specify the repository name',
                'Specify the issue-tracker organization name',
                'Specify the issue-tracker repository/project name',
                'Repository-manager "GitHub" is not configured yet.',
                'Issue-tracker "GitHub" is not configured yet.',
                'Would you like to configure the missing adapters now?',
                ['\[OK\]\sConfiguration file saved successfully\.$', true],
            ],
            $display
        );

        $expected = [
            'repo_adapter' => 'github',
            'issue_tracker' => 'github',
            'repo_org' => 'MyOrg',
            'repo_name' => 'MyRepo',
            'issue_project_org' => 'IOrg',
            'issue_project_name' => 'IRepo',
        ];

        $this->assertFileExists($command->getConfig()->get('local_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_LOCAL));
    }

    public function testLocalConfiguringWithRequiredOptionsInNonInteractive()
    {
        $command = new InitCommand();
        $tester = $this->getCommandTester(
            $command,
            [],
            []
        );

        $tester->execute(
            ['--repo-adapter' => 'github', '--org' => 'MyOrg', '--repo' => 'MyRepo'],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();

        $this->assertCommandOutputMatches(
            [
                'Repository-manager "GitHub" is not configured yet.',
                'Issue-tracker "GitHub" is not configured yet.',
                'Run the "core:configure" command to configure the adapters.',
                ['\[OK\]\sConfiguration file saved successfully\.$', true],
            ],
            $display
        );

        $expected = [
            'repo_adapter' => 'github',
            'issue_tracker' => 'github',
            'repo_org' => 'MyOrg',
            'repo_name' => 'MyRepo',
            'issue_project_org' => 'MyOrg',
            'issue_project_name' => 'MyRepo',
        ];

        $this->assertFileExists($command->getConfig()->get('local_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_LOCAL));
    }

    public function testLocalConfiguringWithAllOptionsInNonInteractive()
    {
        $command = new InitCommand();
        $tester = $this->getCommandTester(
            $command,
            [],
            []
        );

        $tester->execute(
            [
                'command' => $command->getName(),
                '--repo-adapter' => 'github',
                '--org' => 'MyOrg',
                '--repo' => 'MyRepo',
                '--issue-adapter' => 'github',
                '--issue-org' => 'IOrg',
                '--issue-project' => 'IRepo',
            ],
            ['interactive' => false]
        );

        $display = $tester->getDisplay(true);

        $this->assertCommandOutputMatches(
            [
                'Repository-manager "GitHub" is not configured yet.',
                'Issue-tracker "GitHub" is not configured yet.',
                'Run the "core:configure" command to configure the adapters.',
                ['\[OK\]\sConfiguration file saved successfully\.$', true],
            ],
            $display
        );

        $expected = [
            'repo_adapter' => 'github',
            'issue_tracker' => 'github',
            'repo_org' => 'MyOrg',
            'repo_name' => 'MyRepo',
            'issue_project_org' => 'IOrg',
            'issue_project_name' => 'IRepo',
        ];

        $this->assertFileExists($command->getConfig()->get('local_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_LOCAL));
    }

    public function testLocalAutoConfiguringInNonInteractive()
    {
        $command = new InitCommand();
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

        $tester->execute(
            [],
            ['interactive' => false]
        );

        $display = $tester->getDisplay();

        $this->assertNotContains('Run the "core:configure" command to configure the adapters.', $display);
        $this->assertCommandOutputMatches(
            [
                'You did not provide an organization and/or repository name.',
                'Org: "gushphp" / repo: "gush"',
                ['\[OK\]\sConfiguration file saved successfully\.$', true],
            ],
            $display
        );

        $expected = [
            'repo_adapter' => 'github',
            'issue_tracker' => 'github',
            'repo_org' => 'gushphp',
            'repo_name' => 'gush',
            'issue_project_org' => 'gushphp',
            'issue_project_name' => 'gush',
        ];

        $this->assertFileExists($command->getConfig()->get('local_config'));
        $this->assertEquals($expected, $command->getConfig()->toArray(Config::CONFIG_LOCAL));
    }

    public function testGivesErrorWhenNotInGitFolder()
    {
        $command = new InitCommand();
        $tester = $this->getCommandTester(
            $command,
            [],
            [],
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(false)->reveal());
            }
        );

        $this->setExpectedException(
            'Gush\Exception\UserException',
            sprintf(
                'The "%s" command can only be executed from the root of a Git repository.',
                $command->getName()
            )
        );

        $tester->execute(
            [],
            ['interactive' => false] // not relevant for this but prevents hanging on broken test
        );
    }
}
