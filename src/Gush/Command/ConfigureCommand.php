<?php

/*
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Configure the github credentials and the cache folder')
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

        $yaml = new Yaml();
        $content = ['parameters' => $this->config->raw()];

        @unlink($filename);
        if (!@file_put_contents($filename, $yaml->dump($content), 0644)) {
            $output->writeln('<error>It could not save file.</error>');
        }

        $output->writeln('<info>Configuration saved successfully.</info>');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $isAuthenticated = false;
        $username = null;
        $password = null;

        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        $validator = function ($field) {
            if (empty($field)) {
                throw new \InvalidArgumentException('The field cannot be empty.');
            }

            return $field;
        };

        while (!$isAuthenticated) {
            $output->writeln('<comment>Insert your github credentials:</comment>');
            $username = $dialog->askAndValidate(
                $output,
                'username: ',
                $validator
            );
            $password = $dialog->askHiddenResponseAndValidate(
                $output,
                'password: ',
                $validator
            );

            try {
                $isAuthenticated = $this->isGithubCredentialsValid($username, $password);
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        $cacheDir = $dialog->askAndValidate(
            $output,
            "Cache folder [{$this->config->get('cache-dir')}]: ",
            function ($dir) {
                if (!is_dir($dir)) {
                    throw new \InvalidArgumentException('The folder is does not exist.');
                }

                if (!is_writable($dir)) {
                    throw new \InvalidArgumentException('The folder is not writable.');
                }

                return $dir;
            },
            false,
            $this->config->get('cache-dir')
        );

        $this->config->merge(
            [
                'cache-dir' => $cacheDir,
                'github' => [
                    'username' => $username,
                    'password' => $password
                ]
            ]
        );
    }

    /**
     * Validates if the credentials are valid
     *
     * @param  string  $username
     * @param  string  $password
     * @return Boolean
     */
    private function isGithubCredentialsValid($username, $password)
    {
        if (null === $client = $this->getGithubClient()) {
            $client = new Client();
        }

        $client->authenticate($username, $password, Client::AUTH_HTTP_PASSWORD);

        return is_array($client->api('authorizations')->all());
    }
}
