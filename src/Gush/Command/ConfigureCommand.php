<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Github\Client;
use Gush\Factory;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Configure the settings needed to run the Commands
 *
 * @author Daniel Gomes <me@danielcsgomes.com>
 */
class ConfigureCommand extends BaseCommand
{
    /**
     * @var \Gush\Config $config
     */
    private $config;

    protected $authenticationOptions = [
        0 => Client::AUTH_HTTP_PASSWORD,
        1 => Client::AUTH_HTTP_TOKEN,
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('configure')
            ->setDescription('Configure the github credentials and the cache folder')
            ->addOption(
                'adapter',
                'a',
                InputOption::VALUE_OPTIONAL,
                "What adapter should be used? (GitHub)",
                '\\Gush\\Adapter\\GitHubAdapter'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> configure parameters Gush will use:

    <info>$ gush %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->config = Factory::createConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $this->config->get('home').'/.gush.yml';

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
        $this->getApplication()->validateAdapterClass($input->getOption('adapter'));

        $isAuthenticated    = false;
        $username           = null;
        $passwordOrToken    = null;
        $authenticationType = null;
        $versionEyeToken    = null;
        $config             = [];

        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        $validator = function ($field) {
            if (empty($field)) {
                throw new \InvalidArgumentException('The field cannot be empty.');
            }

            return $field;
        };

        while (!$isAuthenticated) {
            $output->writeln('<comment>Enter Hub Connection type:</comment>');
            $authenticationType = $dialog->select(
                $output,
                'Select among these: ',
                $this->authenticationOptions,
                0
            );

            $authenticationType = $this->authenticationOptions[$authenticationType];
            $output->writeln('<comment>Insert your Hub Credentials:</comment>');
            $username            = $dialog->askAndValidate(
                $output,
                'username: ',
                $validator
            );

            $passwordOrTokenText = $authenticationType == Client::AUTH_HTTP_PASSWORD ? 'password: ' : 'token: ';
            $passwordOrToken     = $dialog->askHiddenResponseAndValidate(
                $output,
                $passwordOrTokenText,
                $validator
            );

            $this->config->merge(
                array_merge(
                    $config,
                    [
                        'authentication' => [
                            'username'          => $username,
                            'password-or-token' => $passwordOrToken,
                            'http-auth-type'    => $authenticationType
                        ]
                    ]
                )
            );

            $this->getApplication()->buildAdapter($input, false);
            $config = array_merge(
                $config,
                [$this->getAdapter()->getName() => $this->getAdapter()->doConfiguration($output, $dialog)]
            );

            try {
                $isAuthenticated = $this->isCredentialsValid(
                    $input,
                    $username,
                    $passwordOrToken,
                    $authenticationType
                );
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                $output->writeln("<error>{$e->getMessage()}</error>");
                $output->writeln('');
                $output->writeln('You can create valid access tokens at https://github.com/settings/applications.');
            }
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
            'versioneye token: ',
            $validator
        );

        $this->config->merge(
            array_merge(
                $config,
                [
                    'adapter_class'    => $input->getOption('adapter'),
                    'cache-dir'        => $cacheDir,
                    'versioneye-token' => $versionEyeToken,
                ]
            )
        );
    }

    /**
     * Validates if the credentials are valid
     *
     * @param  string $username
     * @param  string $passwordOrToken
     * @param  string $authenticationType
     *
     * @return Boolean
     */
    private function isCredentialsValid($username, $passwordOrToken, $authenticationType)
    {
        if (null === $adapter = $this->getAdapter()) {
            $this->config->merge([
                'username'          => $username,
                'password-or-token' => $passwordOrToken,
                'http-auth-type'    => $authenticationType
            ]);
            $adapter = $this->getApplication()->buildAdapter($);
        }

        return $adapter->isAuthenticated();
    }
}
