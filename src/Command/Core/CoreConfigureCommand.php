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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_OPTIONAL,
                'What adapter should be used? (github, bitbucket, gitlab)'
            )
            ->addOption(
                'issue_tracker',
                'i',
                InputOption::VALUE_OPTIONAL,
                'What issue tracker should be used? (jira, github, bitbucket, gitlab)'
            )
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
        $issueTrackerName = $input->getOption('issue_tracker');

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        if (null === $adapterName && null === $issueTrackerName) {
            $adapterName = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion('Choose adapter: ', array_keys($adapters))
            );
        } elseif (null !== $adapterName && !array_key_exists($adapterName, $adapters)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The adapter "%s" is invalid. Available adapters are "%s"',
                    $adapterName,
                    implode('", "', array_keys($adapters))
                )
            );
        }

        if (null !== $adapterName) {
            $this->configureAdapter($input, $output, $adapterName);

            $currentDefault = $this->config->get('adapter');
            if ($adapterName !== $currentDefault &&
                $questionHelper->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion(
                        sprintf('Would you like to make "%s" the default adapter?', $adapterName),
                        null === $currentDefault
                    )
                )
            ) {
                $this->config->merge(['adapter' => $adapterName]);
            }
        }

        if (null === $issueTrackerName && null === $input->getOption('adapter')) {
            $selection = array_key_exists($adapterName, $issueTrackers) ? $adapterName : null;
            $issueTrackerName = $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion('Choose issue tracker: ', array_keys($issueTrackers), $selection)
            );
        } elseif (null !== $issueTrackerName && !array_key_exists($issueTrackerName, $issueTrackers)) {
            throw new \Exception(
                sprintf(
                    'The issue tracker "%s" is invalid. Available adapters are "%s"',
                    $issueTrackerName,
                    implode('", "', array_keys($issueTrackers))
                )
            );
        }

        if (null !== $issueTrackerName) {
            $this->configureAdapter($input, $output, $issueTrackerName, 'issue_trackers');

            $currentDefault = $this->config->get('issue_tracker');
            if ($issueTrackerName !== $currentDefault &&
                $questionHelper->ask(
                    $input,
                    $output,
                    new ConfirmationQuestion(
                        sprintf('Would you like to make "%s" the default issue tracker?', $issueTrackerName),
                        null === $currentDefault
                    )
                )
            ) {
                $this->config->merge(['issue_tracker' => $issueTrackerName]);
            }
        }

        $cacheDir = $questionHelper->ask(
            $input,
            $output,
            (new Question(
                "Cache folder [{$this->config->get('cache-dir')}]: ",
                $this->config->get('cache-dir')
            ))->setValidator(
                function ($dir) {
                    if (!is_dir($dir)) {
                        throw new \InvalidArgumentException('Cache folder does not exist.');
                    }

                    if (!is_writable($dir)) {
                        throw new \InvalidArgumentException('Cache folder is not writable.');
                    }

                    return $dir;
                }
            )
        );

        $versionEyeToken = $questionHelper->ask(
            $input,
            $output,
            (new Question('VersionEye token: ', 'NO_TOKEN'))
                ->setValidator(
                    function ($field) {
                        if (empty($field)) {
                            throw new \InvalidArgumentException('This field cannot be empty.');
                        }

                        return $field;
                    }
                )
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
            if ($authenticationAttempts > 50) {
                $output->writeln("<error>To many attempts, aborting.</error>");

                break;
            }

            if ($authenticationAttempts > 0) {
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
