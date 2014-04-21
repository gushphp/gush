<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
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
use Gush\Helper\OutputAwareInterface;
use Gush\Meta as Meta;
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
use Symfony\Component\Process\ProcessBuilder;
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

    /** @var  array */
    protected $adapters = [];

    public function __construct($name = 'Gush', $version = '@package_version@')
    {
        if ('@'.'package_version@' !== $version) {
            $version = ltrim($version, 'v');
        }

        $helperSet = $this->getDefaultHelperSet();
        $helperSet->set(new Helpers\TextHelper());
        $helperSet->set(new Helpers\TableHelper());
        $helperSet->set(new Helpers\ProcessHelper());
        $helperSet->set(new Helpers\EditorHelper());
        $helperSet->set(new Helpers\GitHelper($helperSet->get('process')));
        $helperSet->set(new Helpers\TemplateHelper($helperSet->get('dialog')));
        $helperSet->set(new Helpers\MetaHelper($this->getSupportedMetaFiles()));
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

        $this->registerAdapter('\\Gush\\Adapter\\GitHubAdapter');
        $this->registerAdapter('\\Gush\\Adapter\\BitbucketAdapter');
        $this->registerAdapter('\\Gush\\Adapter\\GitLabAdapter');
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
     * @return Adapter|null
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

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
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
        if ('core:configure' !== $this->getCommandName($input)) {
            $this->readParameters();

            if (null === $this->adapter) {
                if (null === $adapter = $this->config->get('adapter')) {
                    // @TODO: This part should move to a different place
                    $builder = new ProcessBuilder(['git', 'config', '--get', 'remote.origin.url']);
                    $builder
                        ->setWorkingDirectory(getcwd())
                        ->setTimeout(3600)
                    ;
                    $process = $builder->getProcess();

                    $process->run();

                    if (!$process->isSuccessful()) {
                        throw new \RuntimeException($process->getErrorOutput() . 'The adapter type could not be determined. Please run the gush init command');
                    }

                    $remoteUrl = strtolower($process->getOutput());

                    // @TODO: we should get a different way of determining the adapter type based on the remote url
                    if (strpos($remoteUrl, 'github')) {
                        $adapter = 'github';
                    } elseif (strpos($remoteUrl, 'bitbucket')) {
                        $adapter = 'bitbucket';
                    }
                }

                $this->adapter = $this->buildAdapter($adapter);
            }

            if (null === $this->versionEyeClient) {
                $this->versionEyeClient = $this->buildVersionEyeClient();
            }
        }

        foreach ($command->getHelperSet() as $helper) {
            if ($helper instanceof OutputAwareInterface) {
                $helper->setOutput($output);
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

        $homeFilename = $this->config->get('home_config');
        $localFilename = $this->config->get('local_config');

        if (!file_exists($homeFilename)) {
            throw new FileNotFoundException(
                'The .gush.yml file doest not exist, please run the core:configure command.'
            );
        }

        $yaml = new Yaml();

        try {
            $parsed = $yaml->parse($homeFilename);
            $this->config->merge($parsed['parameters']);

            if (!$this->config->isValid()) {
                throw new \RuntimeException(
                    'The .gush.yml is not properly configured. Please run the core:configure command.'
                );
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("{$e->getMessage()}.\nPlease run the core:configure command.");
        }

        // merge the local config
        if (file_exists($localFilename)) {
            try {
                $parsed = $yaml->parse($localFilename);
                $this->config->merge($parsed);

                /*if (!$this->config->isValid()) {
                    throw new \RuntimeException(
                        'The .gush.yml is not properly configured. Please run the core:configure command.'
                    );
                }*/
            } catch (\Exception $e) {
                throw new \RuntimeException("{$e->getMessage()}.\nPlease run the core:configure command.");
            }
        }
    }

    /**
     * Builds the adapter for the application
     *
     * @param string $adapter
     *
     * @return Adapter
     */
    public function buildAdapter($adapter)
    {
        $adapterClass = $this->config->get(sprintf('[adapters][%s][adapter_class]', $adapter));

        $this->validateAdapterClass($adapterClass);

        /** @var Adapter $adapter */
        $adapter = new $adapterClass($this->config);
        $adapter->authenticate();

        $this->setAdapter($adapter);

        return $adapter;
    }

    /**
     * @param string $adapterClass
     */
    public function registerAdapter($adapterClass)
    {
        $name = $this->validateAdapterClass($adapterClass);

        $this->adapters[$name] = $adapterClass;
    }

    /**
     * @return array
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @param string $adapterClass
     *
     * @return string
     * @throws Exception\AdapterException
     */
    public function validateAdapterClass($adapterClass)
    {
        if (!class_exists($adapterClass)) {
            throw new AdapterException(sprintf('The adapter class "%s" doesn\'t exist.', $adapterClass));
        }

        $reflection = new \ReflectionClass($adapterClass);
        $interface  = "Gush\\Adapter\\Adapter";
        if (!$reflection->implementsInterface($interface)) {
            throw new AdapterException(
                sprintf(
                    'The adapter class "%s" does not implement "%s"',
                    $adapterClass,
                    $interface
                )
            );
        }

        return $reflection->getConstant('NAME');
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
        $updateCommand = new UpdateCommand('core:update');
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
            new Cmd\MetaHeaderCommand(),
            new Cmd\PullRequestFixerCommand(),
            new Cmd\ReleaseCreateCommand(),
            new Cmd\ReleaseListCommand(),
            new Cmd\ReleaseRemoveCommand(),
            new Cmd\IssueTakeCommand(),
            new Cmd\IssueCreateCommand(),
            new Cmd\IssueCloseCommand(),
            new Cmd\IssueAssignCommand(),
            new Cmd\IssueLabelListCommand(),
            new Cmd\IssueMilestoneListCommand(),
            new Cmd\IssueShowCommand(),
            new Cmd\IssueListCommand(),
            new Cmd\BranchPushCommand(),
            new Cmd\BranchSyncCommand(),
            new Cmd\BranchDeleteCommand(),
            new Cmd\BranchForkCommand(),
            new Cmd\BranchChangelogCommand(),
            new Cmd\LabelIssuesCommand(),
            new Cmd\CoreConfigureCommand(),
            new Cmd\CoreAliasCommand(),
            new Cmd\PullRequestVersionEyeCommand(),
        ];
    }

    public function getSupportedMetaFiles()
    {
        return [
            'php'  => new Meta\Base,
            'js'   => new Meta\Base,
            'css'  => new Meta\Base,
            'twig' => new Meta\Twig,
        ];
    }
}
