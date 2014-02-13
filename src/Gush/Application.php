<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Gush\Adapter\Adapter;
use Gush\Command as Cmd;
use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Exception\FileNotFoundException;
use Gush\Exception\AdapterException;
use Gush\Helper as Helpers;
use Gush\Subscriber\GitHubSubscriber;
use Gush\Subscriber\TableSubscriber;
use Gush\Subscriber\TemplateSubscriber;
use Guzzle\Http\Client as GuzzleClient;
use KevinGH\Amend\Command as UpdateCommand;
use KevinGH\Amend\Helper as UpdateHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    const MANIFESTO_FILE_URL = 'http://gushphp.org/manifest.json';

    /**
     * @var Config $config The configuration file
     */
    protected $config;

    /**
     * @var null|Adapter $adapter The Hub Adapter
     */
    protected $adapter;

    /**
     * @var \Guzzle\Http\Client $versionEyeClient The VersionEye Client
     */
    protected $versionEyeClient = null;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    public function __construct($name = 'Gush', $version = '@git-version@')
    {
        $helperSet = $this->getDefaultHelperSet();
        $helperSet->set(new Helpers\GitHelper());
        $helperSet->set(new Helpers\TextHelper());
        $helperSet->set(new Helpers\TableHelper());
        $helperSet->set(new Helpers\ProcessHelper());
        $helperSet->set(new Helpers\TemplateHelper($helperSet->get('dialog')));
        $helperSet->set(new UpdateHelper());

        // the parent dispatcher is private and has
        // no accessor, so we set it here so we can access it.
        $this->dispatcher = new EventDispatcher();

        // add our subscribers to the event dispatcher
        $this->dispatcher->addSubscriber(new TableSubscriber());
        $this->dispatcher->addSubscriber(new GitHubSubscriber($helperSet->get('git')));
        $this->dispatcher->addSubscriber(new TemplateSubscriber($helperSet->get('template')));

        // share our dispatcher with the parent class
        $this->setDispatcher($this->dispatcher);

        parent::__construct($name, $version);
        $this->setHelperSet($helperSet);
        $this->addCommands($this->getCommands());
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

    /**
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param Adapter $adapter
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }


    public function setVersionEyeClient(GuzzleClient $versionEyeClient)
    {
        $this->versionEyeClient = $versionEyeClient;
    }

    /**
     * @return \Guzzle\Http\Client
     */
    public function getVersionEyeClient()
    {
        return $this->versionEyeClient;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ('configure' !== $this->getCommandName($input)) {
            $this->readParameters();

            if (null === $this->adapter) {
                $this->buildAdapter($input);
            }

            if (null === $this->versionEyeClient) {
                $this->versionEyeClient = $this->buildVersionEyeClient();
            }
        }

        parent::doRunCommand($command, $input, $output);
    }

    protected function readParameters()
    {
        if ($this->config) {
            return;
        }

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

    /**
     * Builds the adapter for the application
     *
     * @param InputInterface $input
     * @param Boolean        $authenticate
     */
    public function buildAdapter(InputInterface $input, $authenticate = true)
    {
        $adapterClass = $this->config->get('adapter_class');
        if (null === $adapterClass) {
            $adapterClass = "Gush\\Adapter\\GitHubAdapter";
        }

        $this->validateAdapterClass($adapterClass);

        $org = $input->hasOption('org') ? $input->getOption('org') : null;
        $repo = $input->hasOption('org') ? $input->getOption('repo') : null;

        /** @var Adapter $adapter */
        $adapter = new $adapterClass($this->config, $org, $repo);
        if (!$authenticate) {
            $adapter->authenticate();
        }

        $this->setAdapter($adapter);
    }

    public function validateAdapterClass($adapterClass)
    {
        if (!class_exists($adapterClass)) {
            throw new AdapterException(sprintf('The class "%s" doesn\'t exist.', $adapterClass));
        }

        $reflection = new \ReflectionClass($adapterClass);
        $interface  = "Gush\\Adapter\\Adapter";
        if (!$reflection->implementsInterface($interface)) {
            throw new AdapterException(sprintf(
                    'The class "%s" doesnt implement "%s"',
                    $adapterClass,
                    $interface
                ));
        }

        return;
    }

    protected function buildVersionEyeClient()
    {
        $versionEyeToken = $this->config->get('versioneye-token');
        $client = new GuzzleClient();
        $client->setBaseUrl('https://www.versioneye.com');
        $client->setDefaultOption('query', ['api_key' => $versionEyeToken]);

        return $client;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getCommands()
    {
        $updateCommand = new UpdateCommand('update');
        $updateCommand->setManifestUri(self::MANIFESTO_FILE_URL);

        return [
            $updateCommand,
            new Cmd\PullRequestCreateCommand(),
            new Cmd\PullRequestMergeCommand(),
            new Cmd\PullRequestPatOnTheBackCommand(),
            new Cmd\PullRequestSwitchBaseCommand(),
            new Cmd\PullRequestSquashCommand(),
            new Cmd\PullRequestSemVerCommand(),
            new Cmd\FabbotIoCommand(),
            new Cmd\PullRequestFixerCommand(),
            new Cmd\ReleaseCreateCommand(),
            new Cmd\ReleaseListCommand(),
            new Cmd\ReleaseRemoveCommand(),
            new Cmd\IssueTakeCommand(),
            new Cmd\IssueCreateCommand(),
            new Cmd\IssueCloseCommand(),
            new Cmd\IssueLabelListCommand(),
            new Cmd\IssueMilestoneListCommand(),
            new Cmd\IssueShowCommand(),
            new Cmd\IssueListCommand(),
            new Cmd\BranchSyncCommand(),
            new Cmd\BranchDeleteCommand(),
            new Cmd\BranchChangelogCommand(),
            new Cmd\LabelIssuesCommand(),
            new Cmd\ConfigureCommand(),
            new Cmd\PullRequestVersionEyeCommand(),
        ];
    }
}
