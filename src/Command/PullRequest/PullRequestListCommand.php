<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Exception\InvalidStateException;
use Gush\Feature\GitRepoFeature;
use Gush\Feature\TableFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestListCommand extends BaseCommand implements TableFeature, GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    public function getTableDefaultLayout()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:list')
            ->addOption(
                'state',
                null,
                InputOption::VALUE_REQUIRED,
                'For a list of available states, please refer to the adapter documentation'
            )
            ->setDescription('Lists all available pull requests')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command lists all the pull requests:

    <info>$ gush %command.name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $state = $input->getOption('state');
        $adapter = $this->getAdapter();
        $validStates = $adapter->getPullRequestStates();

        if (!empty($state) && !in_array($state, $validStates, true)) {
            throw new InvalidStateException($state, $validStates);
        }

        $adapter = $this->getAdapter();
        $validStates = $adapter->getPullRequestStates();

        if (!empty($state) && !in_array($state, $validStates, true)) {
            throw new InvalidStateException($state, $validStates);
        }

        $pullRequests = $adapter->getPullRequests($state);

        $styleHelper = $this->getHelper('gush_style');
        $styleHelper->title(
            sprintf(
                'Pull requests on %s / %s',
                $input->getOption('org'), $input->getOption('repo')
            )
        );

        $table = $this->getHelper('table');
        $table->setHeaders(['ID', 'Title', 'State', 'Created', 'User', 'Link']);
        $table->formatRows($pullRequests, $this->getRowBuilderCallback());
        $table->setFooter(sprintf('<info>%s pull request(s)</info>', count($pullRequests)));
        $table->render($output, $table);

        return self::COMMAND_SUCCESS;
    }

    private function getRowBuilderCallback()
    {
        return function ($pullRequest) {
            return [
                $pullRequest['number'],
                $pullRequest['title'],
                ucfirst($pullRequest['state']),
                $pullRequest['created_at']->format('Y-m-d H:i'),
                $pullRequest['head']['user'],
                $pullRequest['url'],
            ];
        };
    }
}
