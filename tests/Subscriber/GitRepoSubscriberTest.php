<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
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
    public function testAddsOptionsForGitRepoFeaturedCommand()
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

    public function testDetectsAndInformsMissingAdapterInformation()
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

    public function testDoesNotInformWhenAdapterIsProvided()
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

    public function testDoesNotInformWhenAdapterIsConfigured()
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

    public function testDoesNotInformRepoInfoIsProvided()
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

    public function testDoesNotInformRepoInfoIsConfigured()
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

    public function testAllowsToOverwriteConfiguredAdapter()
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

    public function testAllowsToOverwriteConfiguredOrgAndRepo()
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

    public function testAllowsToOverwriteConfiguredOrgAndRepoForIssue()
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

    public function testThrowsErrorOnInvalidRepoAdapter()
    {
        $this->setExpectedException(
            UserException::class,
            'Adapter "noop-noop" (for repository-management) is not supported'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            ['--repo-adapter' => 'noop-noop'],
            ['repo_adapter' => 'github']
        );
    }

    public function testThrowsErrorOnInvalidIssueAdapter()
    {
        $this->setExpectedException(
            UserException::class,
            'Adapter "noop-noop" (for issue-tracking) is not supported'
        );

        $command = new GitRepoCommand();
        $this->runCommandTest(
            $command,
            ['--issue-adapter' => 'noop-noop'],
            ['repo_adapter' => 'github']
        );
    }

    public function testThrowsErrorWhenNotConfiguredAndNoRemoteIsSetForAutoDetection()
    {
        $this->setExpectedException(
            UserException::class,
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

    public function testThrowsErrorWhenNoOptionsGivenAndNotInGitDir()
    {
        $this->setExpectedException(
            UserException::class,
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

    public function testThrowsErrorWhenAdapterIsSupportedButNotConfigured()
    {
        $this->setExpectedException(
            UserException::class,
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
                        ]
                    ],
                    'github_enterprise' => [
                        'authentication' => [
                            'username' => 'admins',
                            'password' => 'very-un-secretly',
                        ]
                    ]
                ]
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
