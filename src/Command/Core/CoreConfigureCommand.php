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
use Gush\Factory;
use Gush\Factory\AdapterFactory;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\StyleHelper;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

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
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->config = Factory::createConfig(true, false);
        } catch (\Exception $exception) {
            $this->config = Factory::createConfig(false, false);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $this->config->get('home_config');

        $yaml = new Yaml();
        $content = ['parameters' => $this->config->raw()];

        @unlink($filename);

        if (!@file_put_contents($filename, $yaml->dump($content), 0644)) {
            $output->writeln('<error>Configuration file cannot be saved.</error>');
        }

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
                'You are recommended to not skip this step if you configure Gush for the first time.'
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
            $this->handleDefaulting($adapterName, $adapters[$adapterName]);

            if (!$styleHelper->confirm('Do you want to configure other adapters?', false)) {
                break;
        }
        }

        $styleHelper->section('VersionEye configuration');
        $styleHelper->text(
            [
                'VersionEye is a 3rd party service that helps to keep your Composer dependencies up-to-date.',
                'To use this feature you must first get an API token from: https://www.versioneye.com/settings/api',
                'The API token is only used for the <info>version-eye:check</info> command.',
                '',
                "If you don't want to this service use 'NO_TOKEN' as token."
            ]
        );

        $versionEyeToken = $styleHelper->ask(
            'VersionEye token',
            $this->config->get('versioneye-token') ?: 'NO_TOKEN',
            function ($token) use ($application) {
                if ('' === trim($token)) {
                    throw new \InvalidArgumentException('This field cannot be empty.');
                }

                if ('NO_TOKEN' !== $token) {
                    $versionEyeClient = $application->buildVersionEyeClient($token);

                    try {
                        $versionEyeClient->get('/api/v2/projects')->send();
                    } catch (ServerErrorResponseException $e) {
                        if (false !== strrpos($e->getResponse()->getBody(), 'API token not valid.')) {
                            throw new \InvalidArgumentException('API token not valid.');
                        }

                        throw new \InvalidArgumentException('API error: '.$e->getResponse()->getBody());
                    }
                }

                return $token;
            }
        );

        $this->config->merge(['versioneye-token' => $versionEyeToken]);
    }

    private function getAdapterLabels(array $adapters)
    {
        $labels = ['noop' => '  Nothing (skip selection)'];
        $labelPattern = '%s %s (%s)';

        foreach ($adapters as $adapterName => $adapter) {
            $capabilities = [];

            if ($adapter['supports_repository_manager']) {
                $capabilities[] = 'RepositoryManager';
            }

            if ($adapter['supports_issue_tracker']) {
                $capabilities[] = 'IssueTracker';
            }

            $isConfigured =
                $this->config->has(sprintf('[adapters][%s]', $adapterName)) ||
                $this->config->has(sprintf('[issue_trackers][%s]', $adapterName));

            $labels[$adapterName] = sprintf(
                $labelPattern,
                $isConfigured ? '<info>*</info>' : ' ',
                $adapter['label'],
                implode(', ', $capabilities)
            );
        }

        return $labels;
    }

    private function handleDefaulting($adapterName, array $adapterInfo)
    {
        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        $currentDefault = $this->config->get('adapter');

        if ($adapterName !== $currentDefault && $adapterInfo[AdapterFactory::SUPPORT_REPOSITORY_MANAGER] &&
            $styleHelper->confirm(
                sprintf('Would you like to make "%s" the default repository manager?', $adapterInfo['label']),
                null === $currentDefault
            )
        ) {
            $this->config->merge(['adapter' => $adapterName]);
        }

        $currentIssueTracker = $this->config->get('issue_tracker');

        if ($adapterName !== $currentIssueTracker &&
            $adapterInfo[AdapterFactory::SUPPORT_ISSUE_TRACKER] &&
            $styleHelper->confirm(
                sprintf('Would you like to make "%s" the default issue tracker?', $adapterInfo['label']),
                null === $currentDefault
            )
        ) {
            $this->config->merge(['issue_tracker' => $adapterName]);
        }
    }

    private function configureAdapter(InputInterface $input, OutputInterface $output, $adapterName, array $adapter)
    {
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
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

            $authenticationAttempts++;
        }

        if ($isAuthenticated) {
            $rawConfig = $this->config->raw();

            if ($adapter[AdapterFactory::SUPPORT_REPOSITORY_MANAGER]) {
                $rawConfig['adapters'][$adapterName] = $config;
            }

            if ($adapter[AdapterFactory::SUPPORT_ISSUE_TRACKER]) {
                $rawConfig['issue_trackers'][$adapterName] = $config;
            }

            $styleHelper->success(sprintf('The "%s" adapter was successfully authenticated.', $adapter['label']));

            $this->config->merge($rawConfig);
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
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $application->setConfig($this->config);

        $factory = $application->getAdapterFactory();

        if ($adapter[AdapterFactory::SUPPORT_REPOSITORY_MANAGER]) {
            $adapter = $factory->createRepositoryManager($name, $config, $this->config);
        } elseif ($adapter[AdapterFactory::SUPPORT_ISSUE_TRACKER]) {
            $adapter = $factory->createIssueTracker($name, $config, $this->config);
        }

        $adapter->authenticate();

        return $adapter->isAuthenticated();
    }
}
