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
use Gush\Command as Cmd;
use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Exception\FileNotFoundException;
use Gush\Helper as Helpers;
use Gush\Subscriber\GitHubSubscriber;
use Gush\Subscriber\TableSubscriber;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
    protected $githubClient = null;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    public function __construct()
    {
        // instantiate the helpers here so that
        // we can use them inside the subscribers.
        $this->gitHelper = new Helpers\GitHelper();
        $this->textHelper = new Helpers\TextHelper();
        $this->tableHelper = new Helpers\TableHelper();
        $this->processHelper = new Helpers\ProcessHelper();

        // the parent dispatcher is private and has
        // no accessor, so we set it here so we can access it.
        $this->dispatcher = new EventDispatcher();

        // add our subscribers to the event dispatcher
        $this->dispatcher->addSubscriber(new TableSubscriber());
        $this->dispatcher->addSubscriber(new GitHubSubscriber($this->gitHelper));

        // share our dispatcher with the parent class
        $this->setDispatcher($this->dispatcher);

        parent::__construct();

        $this->add(new Cmd\PullRequestCreateCommand());
        $this->add(new Cmd\PullRequestMergeCommand());
        $this->add(new Cmd\PullRequestPatOnTheBackCommand());
        $this->add(new Cmd\PullRequestSwitchBaseCommand());
        $this->add(new Cmd\PullRequestSquashCommand());
        $this->add(new Cmd\PullRequestSemVerCommand());
        $this->add(new Cmd\FabbotIoCommand());
        $this->add(new Cmd\CsFixerCommand());
        $this->add(new Cmd\ReleaseCreateCommand());
        $this->add(new Cmd\ReleaseListCommand());
        $this->add(new Cmd\ReleaseRemoveCommand());
        $this->add(new Cmd\IssueTakeCommand());
        $this->add(new Cmd\IssueCreateCommand());
        $this->add(new Cmd\IssueCloseCommand());
        $this->add(new Cmd\IssueLabelListCommand());
        $this->add(new Cmd\IssueMilestoneListCommand());
        $this->add(new Cmd\IssueShowCommand());
        $this->add(new Cmd\IssueListCommand());
        $this->add(new Cmd\BranchSyncCommand());
        $this->add(new Cmd\BranchDeleteCommand());
        $this->add(new Cmd\BranchChangelogCommand());
        $this->add(new Cmd\LabelIssuesCommand());
        $this->add(new Cmd\ConfigureCommand());
    }

    /**
     * Overrides the add method and dispatch
     * an event enabling subscribers to decorate
     * the command definition.
     *
     * {@inheritDoc}
     */
    public function add(Command $command)
    {
        $this->dispatcher->dispatch(
            GushEvents::DECORATE_DEFINITION,
            new CommandEvent($command)
        );

        parent::add($command);
    }

    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set($this->gitHelper);
        $helperSet->set($this->textHelper);
        $helperSet->set($this->tableHelper);
        $helperSet->set($this->processHelper);

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
                'The .gush.yml file doest not exist, please run the configure command.'
            );
        }

        try {
            $yaml = new Yaml();
            $parsed = $yaml->parse($localFilename);
            $this->config->merge($parsed['parameters']);

            if (!$this->config->isValid()) {
                throw new \RuntimeException(
                    "The '.gush.yml' is not properly configured. Please run the 'configure' command."
                );
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("{$e->getMessage()}.\nPlease run the configure command.");
        }
    }

    protected function buildGithubClient()
    {
        $cachedClient = new CachedHttpClient([
            'cache_dir' => $this->config->get('cache-dir')
        ]);

        $githubCredentials = $this->config->get('github');

        $githubClient = new Client($cachedClient);

        if (Client::AUTH_HTTP_PASSWORD === $githubCredentials['http-auth-type']) {
            $githubClient->authenticate(
                $githubCredentials['username'],
                $githubCredentials['password-or-token'],
                $githubCredentials['http-auth-type']
            );
        } else {
            $githubClient->authenticate(
                $githubCredentials['password-or-token'],
                $githubCredentials['http-auth-type']
            );
        }

        return $githubClient;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
