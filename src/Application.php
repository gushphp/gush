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
use Gush\Subscriber\CommandEndSubscriber;
use Gush\Subscriber\GitRepoSubscriber;
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

    const GUSH_LOGO = <<<LOGO
   _____ _    _  _____ _    _
  / ____| |  | |/ ____| |  | |
 | |  __| |  | | (___ | |__| |
 | | |_ | |  | |\___ \|  __  |
 | |__| | |__| |____) | |  | |
  \_____|\____/|_____/|_|  |_|

LOGO;

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

        try {
            // try setting the config as early as possible, so all the subscribers and helpers can use it
            $this->config = Factory::createConfig();
        } catch (\RuntimeException $exception) {
            // if Gush is not yet configured, then just catch the exception and move along
        }

        $helperSet = $this->getDefaultHelperSet();
        $helperSet->set(new Helpers\FilesystemHelper());
        $helperSet->set(new Helpers\TextHelper());
        $helperSet->set(new Helpers\TableHelper());
        $helperSet->set(new Helpers\ProcessHelper());
        $helperSet->set(new Helpers\EditorHelper());
        $helperSet->set(new Helpers\GitHelper($helperSet->get('process'), $helperSet->get('filesystem')));
        $helperSet->set(new Helpers\TemplateHelper($helperSet->get('question'), $this));
        $helperSet->set(new Helpers\MetaHelper($this->getSupportedMetaFiles()));
        $helperSet->set(new Helpers\AutocompleteHelper());
        $helperSet->set(new UpdateHelper());

        // the parent dispatcher is private and has
        // no accessor, so we set it here so we can access it.
        $this->dispatcher = new EventDispatcher();

        // add our subscribers to the event dispatcher
        $this->dispatcher->addSubscriber(new TableSubscriber());
        $this->dispatcher->addSubscriber(new GitRepoSubscriber($helperSet->get('git')));
        $this->dispatcher->addSubscriber(new TemplateSubscriber($helperSet->get('template')));
        $this->dispatcher->addSubscriber(new CommandEndSubscriber($helperSet->get('filesystem')));

        // share our dispatcher with the parent class
        $this->setDispatcher($this->dispatcher);

        parent::__construct($name, $version);
        $this->setHelperSet($helperSet);
        $this->addCommands($this->getCommands());

        $this->adapterFactory = $adapterFactory;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return self::GUSH_LOGO.PHP_EOL.parent::getHelp();
    }

    /**
     * Overrides the add method and dispatch
     * an event enabling subscribers to decorate
     * the command definition.
     *
     * {@inheritdoc}
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

            if (null === $this->config) {
                $this->config = Factory::createConfig();
            }

            if (null === $this->adapter) {
                if (null === $adapter = $this->config->get('adapter')) {
                    $adapter = $this->determineAdapter();
                }

                $this->buildAdapter($adapter);
            }

            if (null === $this->issueTracker) {
                $issueTracker = $this->config->get('issue_tracker');

                if ($issueTracker) {
                    $this->buildIssueTracker($issueTracker);
                } elseif ($this->adapter instanceof IssueTracker) {
                    $this->issueTracker = $this->adapter;
                } else {
                    $message = 'Adapter "%s" doesn\'t support issue-tracking and no issue tracker is configured. '.
                        PHP_EOL.'Please run the "init" or "core:configure" command to configure a (default) issue '.
                        'tracker.';

                    throw new \RuntimeException(sprintf($message, get_class($this->adapter)));
                }
            }

            if (null === $this->versionEyeClient) {
                $this->versionEyeClient = $this->buildVersionEyeClient();
            }
        }

        /** @var \Symfony\Component\Console\Helper\Helper[] $helperSet */
        $helperSet = $command->getHelperSet();
        foreach ($helperSet as $helper) {
            if ($helper instanceof OutputAwareInterface) {
                $helper->setOutput($output);
            }
        }

        parent::doRunCommand($command, $input, $output);
    }

    /**
     * @return Adapter
     */
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
            throw new \RuntimeException(
                'The adapter type could not be determined (no Git origin configured for this repository). '.
                'Please run the "init" command.'
            );
        }

        $remoteUrl = strtolower($process->getOutput());
        $ignoredAdapters = [];

        foreach (array_keys($this->getAdapterFactory()->getAdapters()) as $adapterName) {
            $config = $this->config->get(sprintf('[adapters][%s]', $adapterName));

            // Adapter is not configured ignore
            if (null === $config) {
                $ignoredAdapters[] = $adapterName;

                continue;
            }

            $adapter = $this->adapterFactory->createAdapter(
                $adapterName,
                $config,
                $this->config
            );

            if ($adapter->supportsRepository($remoteUrl)) {
                return $adapter;
            };
        }

        $exceptionMessage = 'The adapter type could not be determined.';

        if ([] !== $ignoredAdapters) {
            $exceptionMessage .= sprintf(
                'Note, the following adapters (may support this repository) but are currently not configured: "%s".',
                implode('", "', $ignoredAdapters)
            );

            $exceptionMessage .= ' Please configure the adapters or run the "init" command.';
        } else {
            $exceptionMessage .= ' Please run the "init" command.';
        }

        throw new \RuntimeException($exceptionMessage);
    }

    /**
     * Builds the adapter for the application
     *
     * @param string|Adapter $adapter
     * @param array          $config
     *
     * @return Adapter
     *
     * @throws \RuntimeException when the adapter configuration is invalid
     */
    public function buildAdapter($adapter, array $config = null)
    {
        if (!$adapter instanceof Adapter) {
            if (null === $config) {
                $config = $this->config->get(sprintf('[adapters][%s]', $adapter));
            }

            if (null === $config) {
                throw new \RuntimeException(
                    sprintf(
                        'The adapter "%s" is not configured yet. Please run the "core:configure" command to configure.',
                        $adapter
                    )
                );
            }

            $adapter = $this->adapterFactory->createAdapter(
                $adapter,
                $config,
                $this->config
            );
        }

        $adapter->authenticate();
        $this->setAdapter($adapter);

        return $adapter;
    }

    /**
     * Builds the issue tracker for the application.
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
            $config ?: $this->config->get(sprintf('[issue_trackers][%s]', $issueTrackerName)),
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
            new Cmd\PullRequest\PullRequestCreateCommand(),
            new Cmd\PullRequest\PullRequestMergeCommand(),
            new Cmd\PullRequest\PullRequestCloseCommand(),
            new Cmd\PullRequest\PullRequestPatOnTheBackCommand(),
            new Cmd\PullRequest\PullRequestAssignCommand(),
            new Cmd\PullRequest\PullRequestSwitchBaseCommand(),
            new Cmd\PullRequest\PullRequestSquashCommand(),
            new Cmd\PullRequest\PullRequestSemVerCommand(),
            new Cmd\PullRequest\PullRequestListCommand(),
            new Cmd\PullRequest\PullRequestLabelListCommand(),
            new Cmd\PullRequest\PullRequestMilestoneListCommand(),
            new Cmd\PullRequest\PullRequestFixerCommand(),
            new Cmd\Util\DocumentationCommand(),
            new Cmd\Util\VersionEyeCommand(),
            new Cmd\Util\FabbotIoCommand(),
            new Cmd\Util\MetaHeaderCommand(),
            new Cmd\Release\ReleaseCreateCommand(),
            new Cmd\Release\ReleaseListCommand(),
            new Cmd\Release\ReleaseRemoveCommand(),
            new Cmd\Issue\IssueTakeCommand(),
            new Cmd\Issue\IssueCreateCommand(),
            new Cmd\Issue\IssueCloseCommand(),
            new Cmd\Issue\IssueAssignCommand(),
            new Cmd\Issue\IssueLabelListCommand(),
            new Cmd\Issue\IssueMilestoneListCommand(),
            new Cmd\Issue\IssueShowCommand(),
            new Cmd\Issue\IssueListCommand(),
            new Cmd\Issue\LabelIssuesCommand(),
            new Cmd\Branch\BranchPushCommand(),
            new Cmd\Branch\BranchSyncCommand(),
            new Cmd\Branch\BranchDeleteCommand(),
            new Cmd\Branch\BranchForkCommand(),
            new Cmd\Branch\BranchChangelogCommand(),
            new Cmd\Branch\BranchRemoteAddCommand(),
            new Cmd\Core\CoreConfigureCommand(),
            new Cmd\Core\CoreAliasCommand(),
            new Cmd\Core\CoreClearCommand(),
            new Cmd\Core\InitCommand(),
            new Cmd\Core\AutocompleteCommand(),
        ];
    }

    public function getSupportedMetaFiles()
    {
        return [
            'php'  => new Meta\Base,
            'js'   => new Meta\Text,
            'css'  => new Meta\Text,
            'twig' => new Meta\Twig,
        ];
    }
}
