<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Gush\Config;
use Gush\ConfigFactory;
use Gush\Factory\AdapterFactory;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CoreConfigureCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:configure')
            ->setDescription('Configure adapter credentials and the cache folder')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> configure settings Gush will use (including adapters and issue trackers):

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ConfigFactory::dumpToFile($this->getConfig(), Config::CONFIG_SYSTEM);

        $this->getHelper('gush_style')->success('Configuration file saved successfully.');

        return self::COMMAND_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();

        $adapters = $application->getAdapterFactory()->all();
        $labels = $this->getAdapterLabels($adapters);

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $styleHelper->title('Gush configuration');
        $styleHelper->text(
            [
                'The <info>core:configure</info> command will help to configure Gush for usage.',
                'Your authentication credentials are never stored in the local Git repository.',
            ]
        );
        $styleHelper->newLine();

        $styleHelper->section('Adapter configuration');
        $styleHelper->text(
            [
                'Gush uses adapters for repository-management and issue-tracking.',
                'Adapters displayed with a "<info>*</info>" are already configured.',
                'You are recommended to not skip this step if you configure Gush for the first time.',
            ]
        );
        $styleHelper->newLine();

        // Run in a loop to allow multiple selection
        while (true) {
            $adapterName = $styleHelper->numberedChoice('Choose adapter', $labels);

            if ('noop' === $adapterName) {
                break;
            }

            $this->configureAdapter($input, $output, $adapterName, $adapters[$adapterName]);

            if (!$styleHelper->confirm('Do you want to configure other adapters?', false)) {
                break;
            }
        }
    }

    private function getAdapterLabels(array $adapters)
    {
        $labels = ['noop' => '  Nothing (skip selection)'];
        $labelPattern = '%s %s (%s)';

        $config = $this->getConfig();

        foreach ($adapters as $adapterName => $adapter) {
            $capabilities = [];

            if ($adapter['supports_repository_manager']) {
                $capabilities[] = 'RepositoryManager';
            }

            if ($adapter['supports_issue_tracker']) {
                $capabilities[] = 'IssueTracker';
            }

            $labels[$adapterName] = sprintf(
                $labelPattern,
                $config->has(['adapters', $adapterName]) ? '<info>*</info>' : ' ',
                $adapter['label'],
                implode(', ', $capabilities)
            );
        }

        return $labels;
    }

    private function configureAdapter(InputInterface $input, OutputInterface $output, $adapterName, array $adapter)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();

        $isAuthenticated = false;
        $authenticationAttempts = 0;
        $config = [];

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $configurator = $application->getAdapterFactory()->createConfigurator(
            $adapterName,
            $application->getHelperSet()
        );

        while (!$isAuthenticated) {
            // Prevent endless loop with a broken test
            if ($authenticationAttempts > 50) {
                throw new \RuntimeException('Too many attempts, aborting.');
            }

            if ($authenticationAttempts > 0) {
                $styleHelper->error('Authentication failed please try again.');
            }

            try {
                $config = $configurator->interact($input, $output);

                $isAuthenticated = $this->isCredentialsValid($adapterName, $adapter, $config);
            } catch (\Exception $e) {
                $styleHelper->error($e->getMessage());
            }

            ++$authenticationAttempts;
        }

        if ($isAuthenticated) {
            $rawConfig = $this->getConfig()->toArray(Config::CONFIG_SYSTEM);
            $rawConfig['adapters'][$adapterName] = $config;

            $styleHelper->success(sprintf('The "%s" adapter was successfully authenticated.', $adapter['label']));

            $this->getConfig()->merge($rawConfig, Config::CONFIG_SYSTEM);
        }
    }

    /**
     * @param string $name
     * @param array  $adapter
     * @param array  $config
     *
     * @return bool
     */
    private function isCredentialsValid($name, array $adapter, array $config)
    {
        /** @var \Gush\Application $application */
        $application = $this->getApplication();
        $factory = $application->getAdapterFactory();

        if ($adapter[AdapterFactory::SUPPORT_REPOSITORY_MANAGER]) {
            $adapter = $factory->createRepositoryManager($name, $config, $this->getConfig());
        } elseif ($adapter[AdapterFactory::SUPPORT_ISSUE_TRACKER]) {
            $adapter = $factory->createIssueTracker($name, $config, $this->getConfig());
        }

        $adapter->authenticate();

        return $adapter->isAuthenticated();
    }
}
