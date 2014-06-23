<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Gush\Exception\FileNotFoundException;
use Gush\Factory;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Configure the settings needed to run the Commands
 */
class CoreConfigureCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * @var \Gush\Config $config
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:configure')
            ->setDescription('Configure adapter credentials and the cache folder')
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_OPTIONAL,
                'What adapter should be used? (github, bitbucket, gitlab)'
            )
            ->addOption(
                'issue tracker',
                'it',
                InputOption::VALUE_OPTIONAL,
                'What issue tracker should be used? (jira, github, bitbucket, gitlab)'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> configure parameters Gush will use:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->config = Factory::createConfig();
        } catch (FileNotFoundException $exception) {
            $this->config = Factory::createConfig(false);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $this->config->get('home_config');

        $yaml    = new Yaml();
        $content = ['parameters' => $this->config->raw()];

        @unlink($filename);
        if (!@file_put_contents($filename, $yaml->dump($content), 0644)) {
            $output->writeln('<error>Configuration file cannot be saved.</error>');
        }

        $output->writeln('<info>Configuration file saved successfully.</info>');

        return self::COMMAND_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        /** @var \Gush\Application $application */

        $adapters = $application->getAdapterFactory()->getAdapters();
        $issueTrackers = $application->getAdapterFactory()->getIssueTrackers();

        $adapterName = $input->getOption('adapter');
        $issueTrackerName = $input->getOption('issue tracker');
        $selection = 0;

        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        if (null === $adapterName) {
            $selection = $dialog->select(
                $output,
                'Choose adapter: ',
                array_keys($adapters),
                0
            );

            $adapterName = array_keys($adapters)[$selection];
        } elseif (!array_key_exists($adapterName, $adapters)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The adapter "%s" is invalid. Available adapters are "%s"',
                    $adapterName,
                    implode('", "', array_keys($adapters))
                )
            );
        }

        $this->configureAdapter($input, $output, $adapterName);

        $currentDefault = $this->config->get('adapter');
        if ($adapterName !== $currentDefault &&
            $dialog->askConfirmation(
                $output,
                sprintf('Would you like to make "%s" the default adapter?', $adapterName),
                null === $currentDefault
            )
        ) {
            $this->config->merge(['adapter' => $adapterName]);
        }

        if (null === $issueTrackerName) {
            if (!array_key_exists($adapterName, $issueTrackers)) {
                $selection = null;
            }

            $selection = $dialog->select(
                $output,
                'Choose issue tracker: ',
                array_keys($issueTrackers),
                $selection
            );

            $issueTrackerName = array_keys($issueTrackers)[$selection];
        } elseif (!array_key_exists($issueTrackerName, $issueTrackers)) {
            throw new \Exception(
                sprintf(
                    'The issue tracker "%s" is invalid. Available adapters are "%s"',
                    $issueTrackerName,
                    implode('", "', array_keys($issueTrackers))
                )
            );
        }

        $this->configureAdapter($input, $output, $issueTrackerName, 'issue_trackers');

        $currentDefault = $this->config->get('issue_tracker');
        if ($issueTrackerName !== $currentDefault &&
            $dialog->askConfirmation(
                $output,
                sprintf('Would you like to make "%s" the default issue tracker?', $issueTrackerName),
                null === $currentDefault
            )
        ) {
            $this->config->merge(['issue_tracker' => $issueTrackerName]);
        }

        $cacheDir = $dialog->askAndValidate(
            $output,
            "Cache folder [{$this->config->get('cache-dir')}]: ",
            function ($dir) {
                if (!is_dir($dir)) {
                    throw new \InvalidArgumentException('Cache folder does not exist.');
                }

                if (!is_writable($dir)) {
                    throw new \InvalidArgumentException('Cache folder is not writable.');
                }

                return $dir;
            },
            false,
            $this->config->get('cache-dir')
        );

        $versionEyeToken = $dialog->askAndValidate(
            $output,
            'VersionEye token: ',
            function ($field) {
                if (empty($field)) {
                    throw new \InvalidArgumentException('This field cannot be empty.');
                }

                return $field;
            },
            false,
            'NO_TOKEN'
        );

        $this->config->merge(
            [
                'cache-dir' => $cacheDir,
                'versioneye-token' => $versionEyeToken,
            ]
        );
    }

    private function configureAdapter(
        InputInterface $input,
        OutputInterface $output,
        $adapterName,
        $configName = 'adapters'
    ) {
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $isAuthenticated = false;
        $authenticationAttempts = 0;
        $config = [];

        if ('adapters' === $configName) {
            $configurator = $application->getAdapterFactory()->createAdapterConfiguration(
                $adapterName,
                $application->getHelperSet()
            );
        } else {
            $configurator = $application->getAdapterFactory()->createIssueTrackerConfiguration(
                $adapterName,
                $application->getHelperSet()
            );
        }

        while (!$isAuthenticated) {
            // Prevent endless loop with a broken test
            if ($authenticationAttempts > 500) {
                $output->writeln("<error>To many attempts, aborting.</error>");

                break;
            }

            if ($authenticationAttempts > 1) {
                $output->writeln("<error>Authentication failed please try again.</error>");
            }

            $config = $configurator->interact($input, $output);

            try {
                if ('adapters' !== $configName) {
                    $isAuthenticated = $this->isIssueTrackerCredentialsValid($adapterName, $config);
                } else {
                    $isAuthenticated = $this->isAdapterCredentialsValid($adapterName, $config);
                }
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
                $output->writeln('');

                if ('adapters' !== $configName) {
                    $adapter = $this->getIssueTracker();
                } else {
                    $adapter = $this->getAdapter();
                }

                if (null !== $adapter && null !== $url = $adapter->getTokenGenerationUrl()) {
                    $output->writeln("You can create valid access tokens at {$url}.");
                }
            }

            $authenticationAttempts++;
        }

        if ($isAuthenticated) {
            $rawConfig = $this->config->raw();
            $rawConfig[$configName][$adapterName] = $config;

            $this->config->merge($rawConfig);
        }
    }

    private function isAdapterCredentialsValid($name, array $config)
    {
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $application->setConfig($this->config);
        $adapter = $application->buildAdapter($name, $config);

        return $adapter->isAuthenticated();
    }

    private function isIssueTrackerCredentialsValid($name, array $config)
    {
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $issueTracker = $application->buildIssueTracker($name, $config);

        return $issueTracker->isAuthenticated();
    }
}
