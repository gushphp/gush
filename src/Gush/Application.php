<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Github\Client;
use Github\HttpClient\CachedHttpClient;
use Gush\Command\ConfigureCommand;
use Gush\Command\IssueLabelListCommand;
use Gush\Command\IssueListCommand;
use Gush\Command\IssueMilestoneListCommand;
use Gush\Command\IssueShowCommand;
use Gush\Command\LabelIssuesCommand;
use Gush\Command\PullRequestTableCommand;
use Gush\Command\PhpCsFixerCommand;
use Gush\Command\ReleaseCreateCommand;
use Gush\Command\ReleaseListCommand;
use Gush\Command\ReleaseRemoveCommand;
use Gush\Command\TakeIssueCommand;
use Gush\Exception\FileNotFoundException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Gush\Helper\TextHelper;

class Application extends BaseApplication
{
    /**
     * @var Config $config The configuration file
     */
    protected $config;

    /**
     * @var \Github\Client $githubClient The Github Client
     */
    protected $githubClient = null;

    public function __construct()
    {
        parent::__construct();

        $this->add(new TakeIssueCommand());
        $this->add(new PullRequestTableCommand());
        $this->add(new PullRequestMergeCommand());
        $this->add(new PhpCsFixerCommand());
        $this->add(new ReleaseCreateCommand());
        $this->add(new ReleaseListCommand());
        $this->add(new ReleaseRemoveCommand());
        $this->add(new IssueLabelListCommand());
        $this->add(new IssueMilestoneListCommand());
        $this->add(new IssueShowCommand());
        $this->add(new IssueListCommand());
        $this->add(new LabelIssuesCommand());
        $this->add(new ConfigureCommand());
    }

    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new TextHelper);

        return $helperSet;
    }

    public function setGithubClient(Client $githubClient)
    {
        $this->githubClient = $githubClient;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ('configure' !== $this->getCommandName($input)) {
            $this->readParameters();

            if (null === $this->githubClient) {
                $this->githubClient = $this->buildGithubClient();
            }
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

    protected function readParameters()
    {
        $this->config = Factory::createConfig();

        $localFilename = $this->config->get('home').'/.gush.yml';

        if (!file_exists($localFilename)) {
            throw new FileNotFoundException(
                'The \'.gush.yml\' doest not exist, please run the \'configure\' command.'
            );
        }

        try {
            $yaml = new Yaml();
            $parsed = $yaml->parse($localFilename);
            $this->config->merge($parsed['parameters']);

            if (!$this->config->isValid()) {
                throw new \RuntimeException('The \'.gush.yml\' is not properly configured. Please run the \'configure\' command.');
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("{$e->getMessage()}.\nPlease run 'configure' command.");
        }
    }

    protected function buildGithubClient()
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
