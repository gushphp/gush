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
use Gush\Exception\FileNotFoundException;
use Gush\Exception\UserException;
use Gush\Factory;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\StyleHelper;
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
            $this->config = Factory::createConfig(true, false);
        } catch (FileNotFoundException $exception) {
            $this->config = Factory::createConfig(false, false);
        } catch (\RuntimeException $exception) {
            $this->getHelper('gush_style')->getStyle()->error($exception->getMessage());

            $this->config = Factory::createConfig(false, false);
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

        $this->getHelper('gush_style')->success('Configuration file saved successfully.');

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

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

        if (null === $adapterName && null === $issueTrackerName) {
            $adapterName = $styleHelper->askQuestion(
                new ChoiceQuestion('Choose adapter: ', array_keys($adapters))
            );
        } elseif (null !== $adapterName && !array_key_exists($adapterName, $adapters)) {
            throw new UserException(
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
                $styleHelper->askQuestion(
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
            $selection = array_search($adapterName, array_keys($issueTrackers), true);

            if (false === $selection) {
                $selection = null;
            }

            $issueTrackerName = $styleHelper->askQuestion(
                new ChoiceQuestion('Choose issue tracker: ', array_keys($issueTrackers), $selection)
            );
        } elseif (null !== $issueTrackerName && !array_key_exists($issueTrackerName, $issueTrackers)) {
            throw new UserException(
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
                $styleHelper->askQuestion(
                    new ConfirmationQuestion(
                        sprintf('Would you like to make "%s" the default issue tracker?', $issueTrackerName),
                        null === $currentDefault
                    )
                )
            ) {
                $this->config->merge(['issue_tracker' => $issueTrackerName]);
            }
        }

        $cacheDir = $styleHelper->askQuestion(
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

        $versionEyeToken = $styleHelper->askQuestion(
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

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');

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
                $styleHelper->error('Too many attempts, aborting.');

                break;
            }

            if ($authenticationAttempts > 0) {
                $styleHelper->error('Authentication failed please try again.');
            }

            $config = $configurator->interact($input, $output);

            try {
                if ('adapters' !== $configName) {
                    $isAuthenticated = $this->isIssueTrackerCredentialsValid($adapterName, $config);
                } else {
                    $isAuthenticated = $this->isAdapterCredentialsValid($adapterName, $config);
                }
            } catch (\Exception $e) {
                $styleHelper->error($e->getMessage());

                if ('adapters' !== $configName) {
                    $adapter = $this->getIssueTracker();
                } else {
                    $adapter = $this->getAdapter();
                }

                if (null !== $adapter && null !== $url = $adapter->getTokenGenerationUrl()) {
                    $styleHelper->note(['You can create valid access tokens at: ', $url]);
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
