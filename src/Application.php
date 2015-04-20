<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush;

use Gush\Adapter\Adapter;
use Gush\Adapter\IssueTracker;
use Gush\Command as Cmd;
use Gush\Exception\UserException;
use Gush\Factory\AdapterFactory;
use Gush\Helper as Helpers;
use Gush\Helper\OutputAwareInterface;
use Gush\Subscriber\CommandEndSubscriber;
use Gush\Subscriber\CoreInitSubscriber;
use Gush\Subscriber\GitRepoSubscriber;
use Gush\Subscriber\TableSubscriber;
use Gush\Subscriber\TemplateSubscriber;
use KevinGH\Amend\Command as UpdateCommand;
use KevinGH\Amend\Helper as UpdateHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var null|Adapter
     */
    protected $adapter;

    /**
     * @var null|IssueTracker
     */
    protected $issueTracker;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var AdapterFactory
     */
    protected $adapterFactory;

    /**
     * Constructor.
     *
     * @param AdapterFactory $adapterFactory
     * @param Config         $config
     * @param string         $version
     */
    public function __construct(AdapterFactory $adapterFactory, Config $config, $version = '@package_version@')
    {
        if ('@'.'package_version@' !== $version) {
            $version = ltrim($version, 'v');
        }

        parent::__construct('Gush', $version);

        $this->adapterFactory = $adapterFactory;
        $this->config = $config;

        // The parent dispatcher is private and has
        // no accessor, so we set it here to make it accessible.
        $this->dispatcher = new EventDispatcher();

        $this->registerSubscribers();
        $this->addCommands($this->getCommands());
    }

    protected function registerSubscribers()
    {
        $helperSet = $this->getHelperSet();

        $this->dispatcher->addSubscriber(new TableSubscriber());
        $this->dispatcher->addSubscriber(
            new GitRepoSubscriber(
                $this,
                $helperSet->get('git'),
                $helperSet->get('git_config'),
                $helperSet->get('gush_style')
            )
        );

        $this->dispatcher->addSubscriber(
            new CoreInitSubscriber(
                $this,
                $helperSet->get('git'),
                $helperSet->get('git_config'),
                $helperSet->get('gush_style')
            )
        );

        $this->dispatcher->addSubscriber(new TemplateSubscriber($helperSet->get('template')));
        $this->dispatcher->addSubscriber(
            new CommandEndSubscriber($helperSet->get('filesystem'), $helperSet->get('git'))
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new Helpers\FilesystemHelper());
        $helperSet->set(new Helpers\TextHelper());
        $helperSet->set(new Helpers\GushQuestionHelper());
        $helperSet->set(new Helpers\StyleHelper($helperSet->get('gush_question')));
        $helperSet->set(new Helpers\TableHelper());
        $helperSet->set(new Helpers\ProcessHelper());
        $helperSet->set(new Helpers\EditorHelper());
        $helperSet->set(new Helpers\GitConfigHelper($helperSet->get('process'), $this));
        $helperSet->set(
            new Helpers\GitHelper(
                $helperSet->get('process'),
                $helperSet->get('git_config'),
                $helperSet->get('filesystem')
            )
        );
        $helperSet->set(new Helpers\TemplateHelper($helperSet->get('gush_style'), $this));
        $helperSet->set(new Helpers\MetaHelper($this->getSupportedMetaFiles()));
        $helperSet->set(new Helpers\AutocompleteHelper());
        $helperSet->set(new UpdateHelper());

        return $helperSet;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return self::GUSH_LOGO.PHP_EOL.parent::getHelp();
    }

    /**
     * Get the Repository adapter.
     *
     * @return Adapter
     *
     * @throws \RuntimeException
     */
    public function getAdapter()
    {
        if (null === $this->adapter) {
            throw new \RuntimeException(
                'No repo-adapter set, make sure the current command implements "Gush\Feature\GitRepoFeature".'
            );
        }

        return $this->adapter;
    }

    /**
     * Get the IssueTracker adapter.
     *
     * @return IssueTracker
     *
     * @throws \RuntimeException
     */
    public function getIssueTracker()
    {
        if (null === $this->issueTracker) {
            throw new \RuntimeException(
                'No issue-adapter set, make sure the current command implements "Gush\Feature\IssueTrackerRepoFeature".'
            );
        }

        return $this->issueTracker;
    }

    /**
     * Set the Repository Adapter.
     *
     * @param Adapter $adapter
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Set the IssueTracker Adapter.
     *
     * @param IssueTracker $issueTracker
     */
    public function setIssueTracker(IssueTracker $issueTracker)
    {
        $this->issueTracker = $issueTracker;
    }

    /**
     * Get the application configuration.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return EventDispatcher
     */
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
        $helperSet = $command->getHelperSet();

        foreach ($helperSet as $helper) {
            if ($helper instanceof OutputAwareInterface) {
                $helper->setOutput($output);
            }

            if ($helper instanceof InputAwareInterface) {
                $helper->setInput($input);
            }
        }

        $event = new ConsoleCommandEvent($command, $input, $output);
        $this->dispatcher->dispatch(ConsoleEvents::COMMAND, $event);

        try {
            $exitCode = $command->run($input, $output);
        } catch (\Exception $e) {
            $event = new ConsoleExceptionEvent($command, $input, $output, $e, $e->getCode());
            $this->dispatcher->dispatch(ConsoleEvents::EXCEPTION, $event);

            if ($e instanceof UserException) {
                $this->getHelperSet()->get('gush_style')->error($e->getMessages());

                if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                    throw $e;
                }
            } else {
                throw $event->getException();
            }

            $exitCode = $event->getExitCode();
        } finally {
            $event = new ConsoleTerminateEvent($command, $input, $output, $exitCode);
            $this->dispatcher->dispatch(ConsoleEvents::TERMINATE, $event);
        }

        return $event->getExitCode();
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
            new Cmd\HelpCommand(),
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
            new Cmd\Util\FabbotIoCommand(),
            new Cmd\Util\MetaHeaderCommand(),
            new Cmd\Util\MetaConfigureCommand(),
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
            new Cmd\Issue\IssueCopyCommand(),
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
            new Cmd\Repository\RepositoryCreateCommand(),
        ];
    }

    public function getSupportedMetaFiles()
    {
        return [
            'php'  => new Meta\Base(),
            'js'   => new Meta\Text(),
            'css'  => new Meta\Text(),
            'twig' => new Meta\Twig(),
        ];
    }
}
