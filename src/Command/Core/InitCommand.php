<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Gush\Config;
use Gush\ConfigFactory;
use Gush\Feature\GitFolderFeature;
use Gush\Helper\GitHelper;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends BaseCommand implements GitFolderFeature
{
    /**
     * Configures the current command.
     *
     * Actual options are configured by the CoreInitSubscriber.
     */
    protected function configure()
    {
        $this
            ->setName('core:init')
            ->setDescription('Configures a local .gush.yml config file')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> creates .gush.yml file that Gush will use for project in current directory:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');
        $adapters = $this->getAdapters();

        $input->setOption(
            'repo-adapter',
            $styleHelper->numberedChoice(
                'Choose repository-manager',
                $adapters[0],
                GitHelper::undefinedToDefault($input->getOption('repo-adapter'))
            )
        );

        $input->setOption(
            'issue-adapter',
            $styleHelper->numberedChoice(
                'Choose issue-tracker',
                $adapters[1],
                GitHelper::undefinedToDefault($input->getOption('issue-adapter'))
            )
        );

        $input->setOption(
            'org',
            $styleHelper->ask(
                'Specify the repository organization name',
                GitHelper::undefinedToDefault($input->getOption('org'))
            )
        );

        $input->setOption(
            'repo',
            $styleHelper->ask('Specify the repository name', GitHelper::undefinedToDefault($input->getOption('repo')))
        );

        $input->setOption(
            'issue-org',
            $styleHelper->ask(
                'Specify the issue-tracker organization name',
                GitHelper::undefinedToDefault($input->getOption('issue-org'), $input->getOption('org'))
            )
        );

        $input->setOption(
            'issue-project',
            $styleHelper->ask(
                'Specify the issue-tracker repository/project name',
                GitHelper::undefinedToDefault($input->getOption('issue-project'), $input->getOption('repo'))
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();

        $config = $application->getConfig();
        $valid = true;

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $adapters = $this->getAdapters();
        $repositoryManagers = $adapters[0];
        $issueTrackers = $adapters[1];

        $repositoryManager = GitHelper::undefinedToDefault($input->getOption('repo-adapter'));
        $issueTracker = GitHelper::undefinedToDefault($input->getOption('issue-adapter'));

        $org = GitHelper::undefinedToDefault($input->getOption('org'));
        $repo = GitHelper::undefinedToDefault($input->getOption('repo'));

        $issueOrg = GitHelper::undefinedToDefault($input->getOption('issue-org'), $org);
        $issueRepo = GitHelper::undefinedToDefault($input->getOption('issue-project'), $repo);

        $this->validateAdapter($repositoryManager, $repositoryManagers, 'Repository-manager', $valid);
        $this->validateAdapter($issueTracker, $issueTrackers, 'Issue-tracker', $valid);

        if (!$valid) {
            return self::COMMAND_FAILURE;
        }

        $this->checkAdapterConfigured(
            $repositoryManager,
            $repositoryManagers[$repositoryManager],
            'Repository-manager',
            $valid
        );

        $this->checkAdapterConfigured(
            $issueTracker,
            $issueTrackers[$issueTracker],
            'Issue-tracker',
            $valid
        );

        if (!$valid) {
            if ($input->isInteractive() &&
                $styleHelper->confirm('Would you like to configure the missing adapters now?')
            ) {
                $application->doRun(new ArrayInput(['command' => 'core:configure']), $output);
            } else {
                $styleHelper->note(
                    [
                        'You cannot use the selected repository-manager and/or issue-tracker until its configured.',
                        'Run the "core:configure" command to configure the adapters.',
                    ]
                );
            }
        }

        $params = [
            'repo_adapter' => $repositoryManager,
            'issue_tracker' => $issueTracker,
            'repo_org' => $org,
            'repo_name' => $repo,
            'issue_project_org' => $issueOrg,
            'issue_project_name' => $issueRepo,
        ];

        $config->merge($params, Config::CONFIG_LOCAL);

        ConfigFactory::dumpToFile($config, Config::CONFIG_LOCAL);

        $styleHelper->success('Configuration file saved successfully.');

        return self::COMMAND_SUCCESS;
    }

    private function validateAdapter($selected, array $available, $type, &$valid)
    {
        if (!array_key_exists($selected, $available)) {
            $this->getHelper('gush_style')->error(
                sprintf(
                    '%s "%s" is invalid. Available adapters are "%s".',
                    $type,
                    $selected,
                    implode('", "', array_keys($available))
                )
            );

            $valid = false;
        }
    }

    private function checkAdapterConfigured($selected, $label, $typeLabel, &$valid)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();
        $config = $application->getConfig();

        if (!$config->has(['adapters', $selected])) {
            $this->getHelper('gush_style')->note(
                sprintf('%s "%s" is not configured yet.', $typeLabel, $label)
            );

            $valid = false;
        }
    }

    private function getAdapters()
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();
        $adapters = $application->getAdapterFactory()->all();

        static $repositoryManagers, $issueTrackers;

        if (null === $repositoryManagers) {
            $repositoryManagers = [];
            $issueTrackers = [];

            foreach ($adapters as $adapterName => $adapter) {
                if ($adapter['supports_repository_manager']) {
                    $repositoryManagers[$adapterName] = $adapter['label'];
                }

                if ($adapter['supports_issue_tracker']) {
                    $issueTrackers[$adapterName] = $adapter['label'];
                }
            }
        }

        return [$repositoryManagers, $issueTrackers];
    }
}
