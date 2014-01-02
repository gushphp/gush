<?php

/*
 * This file is part of the Manager Tools.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ManagerTools;

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use ManagerTools\Command\ConfigureCommand;
use ManagerTools\Command\IssueListLabelsCommand;
use ManagerTools\Command\IssueListMilestonesCommand;
use ManagerTools\Command\LabelIssuesCommand;
use ManagerTools\Command\PullRequestCommand;
use ManagerTools\Command\ReleaseCreateCommand;
use ManagerTools\Command\ReleaseListCommand;
use ManagerTools\Command\ReleaseRemoveCommand;
use ManagerTools\Exception\FileNotFoundException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    /**
     * @var Config $config The configuration file
     */
    protected $config;
    /**
     * @var \Github\Client $githubClient The Github Client
     */
    protected $githubClient;

    public function __construct()
    {
        parent::__construct();

        $this->add(new PullRequestCommand());
        $this->add(new ReleaseCreateCommand());
        $this->add(new ReleaseListCommand());
        $this->add(new ReleaseRemoveCommand());
        $this->add(new IssueListLabelsCommand());
        $this->add(new IssueListMilestonesCommand());
        $this->add(new LabelIssuesCommand());
        $this->add(new ConfigureCommand());
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ('configure' !== $this->getCommandName($input)) {
            $this->readParameters();
            $this->githubClient = $this->buildGithubClient();
        }

        parent::doRunCommand($command, $input, $output);
    }

    /**
     * @return \Github\Client
     */
    public function getGithubClient()
    {
        return $this->githubClient;
    }

    private function readParameters()
    {
        $this->config = Factory::createConfig();

        $localFilename = $this->config->get('home').'/.manager-tools.yml';

        if (!file_exists($localFilename)) {
            throw new FileNotFoundException(
                'The \'.manager-tools.yml\' doest not exist, please run the \'configure\' command.'
            );
        }

        try {
            $yaml = new Yaml();
            $parsed = $yaml->parse($localFilename);
            $this->config->merge($parsed['parameters']);

            if (!$this->config->isValid()) {
                throw new \RuntimeException('The \'.manager-tools.yml\' is not properly configured. Please run the \'configure\' command.');
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("{$e->getMessage()}.\nPlease run 'configure' command.");
        }
    }

    private function buildGithubClient()
    {
        $cachedClient = new CachedHttpClient(array(
            'cache_dir' => $this->config->get('cache-dir')
        ));

        $githubCredentials = $this->config->get('github');

        $githubClient = new Client($cachedClient);
        $githubClient->authenticate(
            $githubCredentials['username'],
            $githubCredentials['password'],
            Client::AUTH_HTTP_PASSWORD
        );

        return $githubClient;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
