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
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:init')
            ->setDescription('Configures a local .gush.yml config file')
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_OPTIONAL,
                'What repository-manager adapter should be used? (github, bitbucket, gitlab)'
            )
            ->addOption(
                'issue-tracker',
                'it',
                InputOption::VALUE_OPTIONAL,
                'What issue-tracker adapter should be used? (jira, github, bitbucket, gitlab)'
            )
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

        if (null === $input->getOption('adapter')) {
            $input->setOption(
                'adapter',
                $styleHelper->numberedChoice('Choose repository-manager', $adapters[0])
            );
        }

        if (null === $input->getOption('issue-tracker')) {
            $input->setOption(
                'issue-tracker',
                $styleHelper->numberedChoice('Choose issue-tracker', $adapters[1])
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();

        $config = $application->getConfig();
        $filename = $config->get('local_config');
        $valid = true;

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $adapters = $this->getAdapters();
        $repositoryManagers = $adapters[0];
        $issueTrackers = $adapters[1];

        $repositoryManager = $input->getOption('adapter');
        $issueTracker = $input->getOption('issue-tracker');

        $this->validateAdapter($repositoryManager, $repositoryManagers, 'Repository-manager', $valid);
        $this->validateAdapter($issueTracker, $issueTrackers, 'Issue-tracker', $valid);

        if (!$valid) {
            return self::COMMAND_FAILURE;
        }

        $this->checkAdapterConfigured(
            $repositoryManager,
            $repositoryManagers[$repositoryManager],
            'adapters',
            'Repository-manager',
            $valid
        );

        $this->checkAdapterConfigured(
            $issueTracker,
            $issueTrackers[$issueTracker],
            'issue_trackers',
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
                        'Run the "core:configure" command to configure the adapters.'
                    ]
                );
            }
        }

        $params = [
            'adapter' => $repositoryManager,
            'issue_tracker' => $issueTracker,
        ];

        if (file_exists($filename)) {
            $params = array_merge(Yaml::parse(file_get_contents($filename)), $params);
        }

        if (!@file_put_contents($filename, Yaml::dump($params), 0644)) {
            $styleHelper->error(
                'Configuration file cannot be saved, make sure you have write access in the current working directory.'
            );
        }

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

    private function checkAdapterConfigured($selected, $label, $pathType, $typeLabel, &$valid)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();
        $config = $application->getConfig();

        if (!$config->has(sprintf('[%s][%s]', $pathType, $selected))) {
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
