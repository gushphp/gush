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

use Gush\Factory;
use Gush\Feature\GitHubFeature;
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
class CoreConfigureCommand extends BaseCommand implements GitHubFeature
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
        $this->setName('core:configure')
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
        $adapter = $input->getOption('adapter');
        $adapterName = $this->getApplication()->validateAdapterClass($adapter);

        $isAuthenticated    = false;
        $username           = null;
        $passwordOrToken    = null;
        $authenticationType = null;
        $versionEyeToken    = null;

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

            $passwordOrTokenText = $authenticationType == self::AUTH_HTTP_PASSWORD ? 'password: ' : 'token: ';
            $passwordOrToken     = $dialog->askHiddenResponseAndValidate(
                $output,
                $passwordOrTokenText,
                $validator
            );

            $this->config->merge(
                [
                    'adapter_class'  => $input->getOption('adapter'),
                    'authentication' => [
                        'username'          => $username,
                        'password-or-token' => $passwordOrToken,
                        'http-auth-type'    => $authenticationType
                    ],
                    $adapterName => call_user_func_array([$adapter, 'doConfiguration'], [$output, $dialog])
                ]
            );

            try {
                $isAuthenticated = $this->isCredentialsValid($input);
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
                $output->writeln('');
                if (null !== $url = $this->getAdapter()->getTokenGenerationUrl()) {
                    $output->writeln("You can create valid access tokens at {$url}.");
                }
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
            $validator,
            false,
            'NO_TOKEN'
        );

        $this->config->merge(
            [
                'cache-dir'        => $cacheDir,
                'versioneye-token' => $versionEyeToken,
            ]
        );
    }

    private function isCredentialsValid($input)
    {
        $this->getApplication()->setConfig($this->config);
        $adapter = $this->getApplication()->buildAdapter($input);

        return $adapter->isAuthenticated();
    }
}
