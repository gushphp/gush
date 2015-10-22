<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Subscriber;

use Gush\Command\BaseCommand;
use Gush\Config;
use Gush\Exception\UserException;
use Gush\Tests\BaseTestCase;
use Gush\Tests\Fixtures\Command\GitRepoCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

class GitRepoSubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    public function adds_options_for_git_repo_featured_command()
    {
        $command = new GitRepoCommand();
        $commandDef = $command->getDefinition();

        $this->assertFalse($commandDef->hasOption('repo'));

        $this->runCommandTest($command);

        $this->assertTrue($commandDef->hasOption('repo-adapter'));
        $this->assertTrue($commandDef->hasOption('repo'));
        $this->assertTrue($commandDef->hasOption('org'));
        $this->assertTrue($commandDef->hasOption('issue-org'));
        $this->assertTrue($commandDef->hasOption('issue-project'));
    }

    /**
     * @test
     */
    public function detects_and_informs_missing_adapter_information()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest($command);

        $display = $commandTest->getDisplay(true);

        $this->assertContains('You did not set or provide an adapter-name', $display);
        $this->assertContains('"origin" Gush detected "github"', $display);

        $this->assertContains('You did not set or provided an organization and/or repository name.', $display);
        $this->assertContains('Org: "gushphp" / repo: "gush"', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github', $command->getAdapter()->getAdapterName());

        $this->assertEquals('gushphp', $command->getAdapter()->getUsername());
        $this->assertEquals('gush', $command->getAdapter()->getRepository());

        $this->assertEquals('gushphp', $command->getIssueTracker()->getUsername());
        $this->assertEquals('gush', $command->getIssueTracker()->getRepository());
    }

    /**
     * @test
     */
    public function does_not_inform_when_adapter_is_provided()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest($command, ['--repo-adapter' => 'github']);

        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertContains('You did not set or provided an organization and/or repository name.', $display);
        $this->assertContains('Org: "gushphp" / repo: "gush"', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github', $command->getAdapter()->getAdapterName());
    }

    /**
     * @test
     */
    public function does_not_inform_when_adapter_is_configured()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest($command, [], ['repo_adapter' => 'github']);

        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertContains('You did not set or provided an organization and/or repository name.', $display);
        $this->assertContains('Org: "gushphp" / repo: "gush"', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github', $command->getAdapter()->getAdapterName());
    }

    /**
     * @test
     */
    public function does_not_inform_repo_info_is_provided()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest(
            $command,
            ['--repo-adapter' => 'github', '--org' => 'gushphp', '--repo' => 'gush']
        );

        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertNotContains('You did not set or provided an organization and/or repository name.', $display);
        $this->assertNotRegExp('{Org: "(.+)" / repo: "(.+)"}', $display);

        $this->assertNotNull($command->getAdapter());

        $this->assertEquals('gushphp', $command->getAdapter()->getUsername());
        $this->assertEquals('gush', $command->getAdapter()->getRepository());

        $this->assertEquals('gushphp', $command->getIssueTracker()->getUsername());
        $this->assertEquals('gush', $command->getIssueTracker()->getRepository());
    }

    /**
     * @test
     */
    public function does_not_inform_repo_info_is_configured()
    {
        $command = new GitRepoCommand();

        $commandTest = $this->runCommandTest($command, [], ['repo_adapter' => 'github']);
        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertContains('You did not set or provided an organization and/or repository name.', $display);
        $this->assertContains('Org: "gushphp" / repo: "gush"', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github', $command->getAdapter()->getAdapterName());

        $this->assertEquals('gushphp', $command->getAdapter()->getUsername());
        $this->assertEquals('gush', $command->getAdapter()->getRepository());

        $this->assertEquals('gushphp', $command->getIssueTracker()->getUsername());
        $this->assertEquals('gush', $command->getIssueTracker()->getRepository());
    }

    /**
     * @test
     */
    public function allows_to_overwrite_configured_adapter()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest(
            $command,
            ['--repo-adapter' => 'github_enterprise'],
            ['repo_adapter' => 'github']
        );

        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github_enterprise', $command->getAdapter()->getAdapterName());
    }

    /**
     * @test
     */
    public function allows_to_overwrite_configured_org_and_repo()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest(
            $command,
            ['--repo-adapter' => 'github_enterprise', '--org' => 'cordoval', '--repo' => 'gush-sandbox'],
            ['repo_adapter' => 'github', 'repo_org' => 'gushphp', 'repo_name' => 'gush']
        );

        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github_enterprise', $command->getAdapter()->getAdapterName());

        $this->assertEquals('cordoval', $command->getAdapter()->getUsername());
        $this->assertEquals('gush-sandbox', $command->getAdapter()->getRepository());

        // This correct as the issue-org/repo is not set and so inherits
        // from the adapter-org/repo config. The option only overwrites the adapter.
        $this->assertEquals('gushphp', $command->getIssueTracker()->getUsername());
        $this->assertEquals('gush', $command->getIssueTracker()->getRepository());
    }

    /**
     * @test
     */
    public function allows_to_overwrite_configured_org_and_repo_for_issue()
    {
        $command = new GitRepoCommand();
        $commandTest = $this->runCommandTest(
            $command,
            ['--repo-adapter' => 'github_enterprise', '--issue-org' => 'cordoval', '--issue-project' => 'gush-sandbox'],
            ['repo_adapter' => 'github', 'repo_org' => 'gushphp', 'repo_repo' => 'gush']
        );

        $display = $commandTest->getDisplay(true);

        $this->assertNotContains('You did not set or provide an adapter-name', $display);

        $this->assertNotNull($command->getAdapter());
        $this->assertEquals('github_enterprise', $command->getAdapter()->getAdapterName());

        $this->assertEquals('gushphp', $command->getAdapter()->getUsername());
        $this->assertEquals('gush', $command->getAdapter()->getRepository());

        $this->assertEquals('cordoval', $command->getIssueTracker()->getUsername());
        $this->assertEquals('gush-sandbox', $command->getIssueTracker()->getRepository());
    }

    /**
     * @test
     */
    public function throws_error_on_invalid_repo_adapter()
    {
        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Adapter "noop-noop" (for repository-management) is not supported'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            ['--repo-adapter' => 'noop-noop'],
            ['repo_adapter' => 'github']
        );
    }

    /**
     * @test
     */
    public function throws_error_on_invalid_issue_adapter()
    {
        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Adapter "noop-noop" (for issue-tracking) is not supported'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            ['--issue-adapter' => 'noop-noop'],
            ['repo_adapter' => 'github']
        );
    }

    /**
     * @test
     */
    public function throws_error_when_not_configured_and_no_remote_is_set_for_auto_detection()
    {
        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Unable to get the repository information, Git remote "origin" should be set for automatic detection'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            [],
            [],
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitConfigHelper(false)->reveal());
            }
        );
    }

    /**
     * @test
     */
    public function throws_error_when_no_options_given_and_not_in_git_dir()
    {
        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Provide the --org and --repo options when your are outside of a Git directory.'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            [],
            [],
            function (HelperSet $helperSet) {
                $helperSet->set($this->getGitHelper(false)->reveal());
            }
        );
    }

    /**
     * @test
     */
    public function throws_error_when_adapter_is_supported_but_not_configured()
    {
        $this->setExpectedException(
            'Gush\Exception\UserException',
            'Adapter "jira" (for issue-tracking) is not configured yet.'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            [],
            ['issue_tracker' => 'jira']
        );
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

    /**
     * @param BaseCommand   $command
     * @param array         $input
     * @param array         $localConfig
     * @param \Closure|null $helperSetManipulator
     *
     * @return CommandTester
     */
    private function runCommandTest(
        BaseCommand $command,
        array $input = [],
        array $localConfig = [],
        $helperSetManipulator = null
    ) {
        $config = new Config(
            '/home/user',
            '/temp/gush',
            [
                'adapters' => [
                    'github' => [
                        'authentication' => [
                            'username' => 'cordoval',
                            'password' => 'very-un-secret',
                        ],
                    ],
                    'github_enterprise' => [
                        'authentication' => [
                            'username' => 'admins',
                            'password' => 'very-un-secretly',
                        ],
                    ],
                ],
            ],
            '/data/repo-dir',
            $localConfig
        );

        $application = $this->getApplication($config, $helperSetManipulator);
        $command->setApplication($application);

        $commandTest = new CommandTester($command);
        $commandTest->execute(array_merge($input, ['command' => $command->getName()]), ['decorated' => false]);

        return $commandTest;
    }
}
