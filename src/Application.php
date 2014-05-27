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
use Gush\Adapter\IssueTracker;
use Gush\Command as Cmd;
use Gush\Event\CommandEvent;
use Gush\Event\GushEvents;
use Gush\Factory\AdapterFactory;
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
     * @var null|IssueTracker IssueTracker
     */
    protected $issueTracker;

    /**
     * @var \Guzzle\Http\Client $versionEyeClient The VersionEye Client
     */
    protected $versionEyeClient = null;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    public function __construct(AdapterFactory $adapterFactory, $name = 'Gush', $version = '@package_version@')
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

        $this->adapterFactory = $adapterFactory;
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
     * @param IssueTracker $issueTracker
     */
    public function setIssueTracker($issueTracker)
    {
        $this->issueTracker = $issueTracker;
    }

    /**
     * @return IssueTracker|null
     */
    public function getIssueTracker()
    {
        return $this->issueTracker;
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
     * @return AdapterFactory
     */
    public function getAdapterFactory()
    {
        return $this->adapterFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ('core:configure' !== $this->getCommandName($input)) {
            $this->config = Factory::createConfig();
            $adapter = null;

            if (null === $this->adapter) {
                if (null === $adapter = $this->config->get('adapter')) {
                    $adapter = $this->determineAdapter();
                }

                $this->adapter = $this->buildAdapter($adapter);
            }

            if (null === $this->issueTracker) {
                if (null === $issueTracker = $this->config->get('issue_tracker')) {
                    $issueTracker = $adapter ?: $this->determineAdapter();
                }

                $this->issueTracker = $this->buildIssueTracker($issueTracker);
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

    private function determineAdapter()
    {
        $builder = new ProcessBuilder(['git', 'config', '--get', 'remote.origin.url']);
        $builder
            ->setWorkingDirectory(getcwd())
            ->setTimeout(3600)
        ;
        $process = $builder->getProcess();

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('The adapter type could not be determined. Please run the init command');
        }

        $remoteUrl = strtolower($process->getOutput());

        if (strpos($remoteUrl, 'github.com')) {
            return 'github';
        }

        if (strpos($remoteUrl, 'bitbucket.org')) {
            return 'bitbucket';
        }

        if (strpos($remoteUrl, 'gitlab.com')) {
            return 'gitlab';
        }

        return 'github';
    }

    /**
     * Builds the adapter for the application
     *
     * @param string $adapterName
     * @param array  $config
     *
     * @return Adapter
     */
    public function buildAdapter($adapterName, array $config = null)
    {
        $adapter = $this->adapterFactory->createAdapter(
            $adapterName,
            $config ?: $this->config->get(sprintf('[adapters][%s][config]', $adapterName)),
            $this->config
        );

        $adapter->authenticate();
        $this->setAdapter($adapter);

        return $adapter;
    }

    /**
     * Builds the issue-tracker for the application.
     *
     * @param string $issueTrackerName
     * @param array  $config
     *
     * @return IssueTracker
     */
    public function buildIssueTracker($issueTrackerName, array $config = null)
    {
        $issueTracker = $this->adapterFactory->createIssueTracker(
            $issueTrackerName,
            $config ?: $this->config->get(sprintf('[issue_trackers][%s][config]', $issueTrackerName)),
            $this->config
        );

        $issueTracker->authenticate();
        $this->setIssueTracker($issueTracker);

        return $issueTracker;
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
            new Cmd\PullRequestListCommand(),
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
            new Cmd\InitCommand(),
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
