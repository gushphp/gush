<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Command\Core\InitCommand;
use Gush\Config;
use Gush\Event\GushEvents;
use Gush\Exception\UserException;
use Gush\Factory\AdapterFactory;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputOption;

class CoreInitSubscriber extends BaseGitRepoSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            GushEvents::DECORATE_DEFINITION => 'decorateDefinition',
            GushEvents::INITIALIZE => 'initialize',
        ];
    }

    public function decorateDefinition(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();

        if (!$command instanceof InitCommand) {
            return;
        }

        $adapterName = $this->application->getConfig()->get('repo_adapter', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_ADAPTER);
        $issueTracker = $this->application->getConfig()->get('issue_tracker', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_ADAPTER);

        $command
            ->addOption(
                'repo-adapter',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Adapter-name of the repository-manager (%s)',
                    $this->getSupportedAdapters(AdapterFactory::SUPPORT_REPOSITORY_MANAGER)
                ),
                $adapterName
            )
            ->addOption(
                'issue-adapter',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Adapter-name of the issue-tracker (%s)',
                    $this->getSupportedAdapters(AdapterFactory::SUPPORT_ISSUE_TRACKER)
                ),
                $issueTracker
            )

            ->addOption(
                'org',
                'o',
                InputOption::VALUE_REQUIRED,
                'Name of the Git organization',
                $this->application->getConfig()->get('repo_org', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_ORG)
            )
            ->addOption(
                'repo',
                'r',
                InputOption::VALUE_REQUIRED,
                'Name of the Git repository',
                $this->application->getConfig()->get('repo_name', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_REPO)
            )

            ->addOption(
                'issue-org',
                'io',
                InputOption::VALUE_REQUIRED,
                'Name of the issue-tracker organization',
                $this->application->getConfig()->getFirstNotNull(
                    ['issue_project_org', 'repo_org'],
                    Config::CONFIG_LOCAL,
                    GitHelper::UNDEFINED_ORG
                )
            )
            ->addOption(
                'issue-project',
                'ip',
                InputOption::VALUE_REQUIRED,
                'Repository/Project name of the issue-tracker',
                $this->application->getConfig()->getFirstNotNull(
                    ['issue_project', 'repo_name'],
                    Config::CONFIG_LOCAL,
                    GitHelper::UNDEFINED_REPO
                )
            )
        ;
    }

    /** 
     * Use this for detecting the org and repo.
     *
     * Add the options to decorateDefinition.
     *
     * @param ConsoleEvent $event
     */
    public function initialize(ConsoleEvent $event)
    {
        $command = $event->getCommand();

        if (!$command instanceof InitCommand) {
            return;
        }

        if (!$this->gitHelper->isGitFolder()) {
            throw new UserException(
                sprintf(
                    'You can only run the "%s" command when you are in a Git directory.',
                    $command->getName()
                )
            );
        }

        $input = $event->getInput();

        $input->setOption(
            'repo-adapter',
            GitHelper::undefinedToDefault($input->getOption('repo-adapter'), $this->detectAdapterName())
        );

        $input->setOption(
            'issue-adapter',
            GitHelper::undefinedToDefault(
                $input->getOption('issue-adapter'),
                $input->getOption('repo-adapter')
            )
        );

        if (!$this->application->getAdapterFactory()->supports($input->getOption('repo-adapter'), AdapterFactory::SUPPORT_REPOSITORY_MANAGER)) {
            return;
        }

        $org = GitHelper::undefinedToDefault($input->getOption('org'));
        $repo = GitHelper::undefinedToDefault($input->getOption('repo'));

        if (null === $org || null === $repo) {
            list($org, $repo) = $this->getRepositoryReference(
                $this->getAdapter($input->getOption('repo-adapter')),
                $org,
                $repo
            );

            if (!$input->isInteractive()) {
                $this->styleHelper->note(
                    [
                        'You did not provide an organization and/or repository name.',
                        'Gush automatically detected the missing information.',
                        sprintf('Org: "%s" / repo: "%s"', $org, $repo),
                    ]
                );
            }

            $input->setOption('org', $org);
            $input->setOption('repo', $repo);
        }

        $input->setOption('issue-org', GitHelper::undefinedToDefault($input->getOption('issue-org'), $org));
        $input->setOption('issue-project', GitHelper::undefinedToDefault($input->getOption('issue-project'), $repo));
    }

    /**
     * @return null|string
     */
    private function detectAdapterName()
    {
        $adapterFactory = $this->application->getAdapterFactory();
        $appConfig = $this->application->getConfig();

        $remote = $this->findRemoteName();

        if (null === $remote) {
            return;
        }

        $remoteUrl = $this->gitConfigHelper->getGitConfig('remote.'.$remote.'.url');
        $adapters = $adapterFactory->allOfType(AdapterFactory::SUPPORT_REPOSITORY_MANAGER);

        foreach ($adapters as $adapterName => $adapterInfo) {
            if (null === $config = $appConfig->get(['adapters', $adapterName])) {
                continue;
            }

            $adapter = $adapterFactory->createRepositoryManager($adapterName, $config, $appConfig);

            if ($adapter->supportsRepository($remoteUrl)) {
                return $adapterName;
            };
        }

        return;
    }
}
