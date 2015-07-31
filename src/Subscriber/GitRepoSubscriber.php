<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Subscriber;

use Gush\Command\BaseCommand;
use Gush\Config;
use Gush\Event\GushEvents;
use Gush\Exception\UserException;
use Gush\Factory\AdapterFactory;
use Gush\Feature\GitRepoFeature;
use Gush\Feature\IssueTrackerRepoFeature;
use Gush\Helper\GitHelper;
use Gush\Util\ConfigUtil;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class GitRepoSubscriber extends BaseGitRepoSubscriber
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
        /** @var GitRepoFeature|BaseCommand $command */
        $command = $event->getCommand();

        if (!$command instanceof GitRepoFeature) {
            return;
        }

        $command
            ->addOption(
                'repo-adapter',
                'ra',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Adapter-name of the repository-manager (%s)',
                    $this->getSupportedAdapters(AdapterFactory::SUPPORT_REPOSITORY_MANAGER)
                ),
                $this->application->getConfig()->get('repo_adapter', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_ADAPTER)
            )
        ;

        $command
            ->addOption(
                'repo-adapter-config',
                'rac',
                InputOption::VALUE_REQUIRED,
                'Config of the repository-manager',
                $this->application->getConfig()->get('repo_adapter_config', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_ADAPTER)
            )
        ;

        // Repository reference information;
        // The information is first loaded from the configuration.
        // UNDEFINED is used as default when the configuration is not available.
        //
        // When UNDEFINED is used the initialize() method of this class autodetects
        // the org and repo-name using the Git remote, and resolves the final repository (no-fork)
        // reference. This cant be done here as the adapter is not available at this point.
        $command
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
        ;

        if ($command instanceof IssueTrackerRepoFeature) {
            $this->setIssueTrackerDef($command);
        }
    }

    /**
     * Set Issue-tracker configuration.
     *
     * When no explicit configuration for the issue-tracker is set
     * the repository information is reused (when possible).
     *
     * @param BaseCommand $command
     */
    private function setIssueTrackerDef(BaseCommand $command)
    {
        $issueTracker = $this->application->getConfig()->get(
            'issue_tracker',
            Config::CONFIG_LOCAL,
            GitHelper::UNDEFINED_ADAPTER
        );

        $command
            ->addOption(
                'issue-adapter',
                'ia',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Adapter-name of the issue-tracker (%s)',
                    $this->getSupportedAdapters(AdapterFactory::SUPPORT_ISSUE_TRACKER)
                ),
                $issueTracker
            )
            ->addOption(
                'issue-adapter-config',
                'iac',
                InputOption::VALUE_REQUIRED,
                'Config of the issue-tracker',
                $this->application->getConfig()->get('issue_tracker_config', Config::CONFIG_LOCAL, GitHelper::UNDEFINED_ADAPTER)
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
                'Repository/project name of the issue-tracker in the organization',
                $this->application->getConfig()->getFirstNotNull(
                    ['issue_project_name', 'repo_name'],
                    Config::CONFIG_LOCAL,
                    GitHelper::UNDEFINED_REPO
                )
            )
        ;
    }

    public function initialize(ConsoleCommandEvent $event)
    {
        /** @var GitRepoFeature|BaseCommand $command */
        $command = $event->getCommand();

        if (!$command instanceof GitRepoFeature) {
            return;
        }

        $input = $event->getInput();

        if (GitHelper::UNDEFINED_ADAPTER === $input->getOption('repo-adapter')) {
            $input->setOption('repo-adapter', $this->detectAdapterName());
        }

        $adapterFactory = $this->application->getAdapterFactory();
        $adapterName = $input->getOption('repo-adapter');

        if ($input->hasOption('issue-adapter') &&
            GitHelper::UNDEFINED_ADAPTER === $input->getOption('issue-adapter') &&
            $adapterFactory->supports($adapterName, AdapterFactory::SUPPORT_ISSUE_TRACKER)
        ) {
            $input->setOption('issue-adapter', $adapterName);
        }

        $this->validateAdaptersConfig($input);

        $adapter = $this->getAdapter($adapterName, $input->getOption('repo-adapter-config'));
        $org = GitHelper::undefinedToDefault($input->getOption('org'));
        $repo = GitHelper::undefinedToDefault($input->getOption('repo'));

        $this->application->setAdapter($adapter);

        // When no org and/or repo is set, determine this from the git remote,
        // but warn its better to run "core:init" as this autodetection process
        // is much slower!
        if (null === $org || null === $repo) {
            if (!$this->gitHelper->isGitFolder()) {
                throw new UserException(
                    'Provide the --org and --repo options when your are outside of a Git directory.'
                );
            }

            list($org, $repo) = $this->getRepositoryReference($adapter, $org, $repo);

            $this->styleHelper->note(
                [
                    'You did not set or provided an organization and/or repository name.',
                    'Gush automatically detected the missing information.',
                    sprintf('Org: "%s" / repo: "%s"', $org, $repo),
                    'But for future reference and better performance it is advised to run "core:init".'
                ]
            );
        }

        $input->setOption('org', $org);
        $input->setOption('repo', $repo);

        $adapter
            ->setRepository($repo)
            ->setUsername($org)
        ;

        if ($command instanceof IssueTrackerRepoFeature) {
            $this->initializeIssueTracker($event, $org, $repo, $adapterName);
        }
    }

    private function validateAdaptersConfig(InputInterface $input)
    {
        $repositoryManager = $input->getOption('repo-adapter');
        $repositoryManagerConfig = $input->getOption('repo-adapter-config');

        $errors = [];

        $this->checkAdapterConfigured(
            $repositoryManager,
            $repositoryManagerConfig,
            'repository-management',
            AdapterFactory::SUPPORT_REPOSITORY_MANAGER,
            $errors
        );

        if ($input->hasOption('issue-adapter')) {
            $issueTracker = $input->getOption('issue-adapter');
            $issueTrackerConfig = $input->getOption('issue-adapter-config');

            $this->checkAdapterConfigured(
                $issueTracker,
                $issueTrackerConfig,
                'issue-tracking',
                AdapterFactory::SUPPORT_ISSUE_TRACKER,
                $errors
            );
        }

        if ($errors) {
            $errors[] = 'Please run the "core:configure" command.';

            throw new UserException($errors);
        }
    }

    /**
     * @param string   $adapter
     * @param array    $adapterConfig
     * @param string   $typeLabel
     * @param string   $supports
     * @param string[] $errors
     */
    private function checkAdapterConfigured($adapter, $adapterConfig, $typeLabel, $supports, array &$errors)
    {
        $adapterFactory = $this->application->getAdapterFactory();

        if (!$adapterFactory->supports($adapter, $supports)) {
            $errors[] = sprintf(
                'Adapter "%s" (for %s) is not supported, supported %2$s adapters are: "%3$s"',
                $adapter,
                $typeLabel,
                implode('", "', array_keys($adapterFactory->allOfType($supports)))
            );

            return;
        }

        $identifier = ConfigUtil::generateConfigurationIdentifier($adapter, $adapterConfig);
        $config = $this->application->getConfig();

        if (!$config->has(['adapters', $identifier], Config::CONFIG_SYSTEM)) {
            $errors[] = sprintf('Adapter "%s" (for %s) is not configured yet.', $identifier, $typeLabel);
        }
    }

    /**
     * @param ConsoleCommandEvent $event
     * @param string              $org
     * @param string              $repo
     * @param string              $adapterConfig
     */
    private function initializeIssueTracker(ConsoleCommandEvent $event, $org, $repo, $adapterConfig)
    {
        $input = $event->getInput();

        $issueOrg = GitHelper::undefinedToDefault($input->getOption('issue-org'), $org);
        $issueRepo = GitHelper::undefinedToDefault($input->getOption('issue-project'), $repo);
        $issueAdapterName = $input->getOption('issue-adapter') ?: $adapterConfig;

        $input->setOption('issue-org', $issueOrg);
        $input->setOption('issue-project', $issueRepo);
        $input->setOption('issue-adapter', $issueAdapterName);

        $identifier = ConfigUtil::generateConfigurationIdentifier($issueAdapterName, $input->getOption('issue-adapter-config'));
        $config = $this->application->getConfig()->get(['adapters', $identifier], Config::CONFIG_SYSTEM);

        $issueTracker = $this->application->getAdapterFactory()->createIssueTracker(
            $issueAdapterName,
            $config,
            $this->application->getConfig()
        );

        $issueTracker->authenticate();

        /** @var \Gush\Adapter\BaseIssueTracker $issueTracker */
        $issueTracker
            ->setRepository($issueRepo)
            ->setUsername($issueOrg)
        ;

        $this->application->setIssueTracker($issueTracker);
    }

    private function detectAdapterName()
    {
        $adapterFactory = $this->application->getAdapterFactory();
        $appConfig = $this->application->getConfig();

        $remote = $this->findRemoteName(false);
        $remoteUrl = $this->gitConfigHelper->getGitConfig('remote.'.$remote.'.url');

        $adapters = $adapterFactory->allOfType(AdapterFactory::SUPPORT_REPOSITORY_MANAGER);
        $ignoredAdapters = [];

        foreach ($adapters as $adapterName => $adapterInfo) {
            $config = $appConfig->get(['adapters', $adapterName]);

            // Adapter is not configured ignore
            if (null === $config) {
                $ignoredAdapters[] = $adapterName;

                continue;
            }

            $adapter = $adapterFactory->createRepositoryManager($adapterName, $config, $appConfig);

            if ($adapter->supportsRepository($remoteUrl)) {
                $this->styleHelper->note(
                    [
                        'You did not set or provide an adapter-name for the repository-manager and/or issue-tracker.',
                        sprintf(
                            'Based on your Git remote "%s" Gush detected "%s" is properly the correct adapter.',
                            $remote,
                            $adapterName
                        ),
                        'But for future reference and better performance it is advised to run "core:init".'
                    ]
                );

                return $adapterName;
            };
        }

        $exceptionMessage = 'The adapter type could not be determined.';

        if ([] !== $ignoredAdapters) {
            $exceptionMessage .= sprintf(
                'The following adapters (may support this repository) but are currently not configured: "%s".',
                implode('", "', $ignoredAdapters)
            );

            $exceptionMessage .= ' Please configure the adapters or run the "core:init" command.';
        } else {
            $exceptionMessage .= ' Please run the "core:init" command.';
        }

        throw new UserException($exceptionMessage);
    }

}
