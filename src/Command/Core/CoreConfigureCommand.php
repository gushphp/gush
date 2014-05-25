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
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CoreConfigureCommand extends BaseCommand implements GitRepoFeature
{
    const AUTH_HTTP_PASSWORD = 'http_password';
    const AUTH_HTTP_TOKEN = 'http_token';

    /**
     * @var \Gush\Config $config
     */
    private $config;

    protected $authenticationOptions = [
        0 => self::AUTH_HTTP_PASSWORD,
        1 => self::AUTH_HTTP_TOKEN,
    ];

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
                "What adapter should be used? (github, bitbucket, gitlab)"
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
        $adapterName = $input->getOption('adapter');
        $versionEyeToken = null;

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

    private function configureAdapter(InputInterface $input, OutputInterface $output, $adapterName)
    {
        $application = $this->getApplication();
        /** @var \Gush\Application $application */
        $isAuthenticated = false;
        $authenticationAttempts = 0;
        $config = [];

        $configurator = $application->getAdapterFactory()->createAdapterConfiguration(
            $adapterName,
            $application->getHelperSet()
        );

        while (!$isAuthenticated) {
            // Prevent endless loop with a broken test
            if ($authenticationAttempts > 500) {
                $output->writeln("<error>To many attempts, aborting.</error>");

                break;
            }

            $config = $configurator->interact($input, $output);

            try {
                $isAuthenticated = $this->isAdapterCredentialsValid($adapterName, $config);
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
                $output->writeln('');

                if (null !== $this->getAdapter() && null !== $url = $this->getAdapter()->getTokenGenerationUrl()) {
                    $output->writeln("You can create valid access tokens at {$url}.");
                }
            }

            $authenticationAttempts++;
        }

        if ($isAuthenticated) {
            $rawConfig = $this->config->raw();
            $rawConfig['adapters'][$adapterName] = $config;

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
}
