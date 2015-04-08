<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IssueTakeCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:take')
            ->setDescription('Takes an issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Number of the issue')
            ->addArgument('base_branch', InputArgument::OPTIONAL, 'Name of the base branch to checkout from')
            ->addOption(
                'remote',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the Git remote (default is origin)',
                null
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command takes an issue from issue tracker repository list:

    <info>$ gush %command.name% 3</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $remote = $input->getOption('remote') ?: 'origin';
        $issueNumber = $input->getArgument('issue_number');
        $baseBranch = $input->getArgument('base_branch');

        $config = $this->getApplication()->getConfig();
        /** @var \Gush\Config $config */

        if (null === $baseBranch) {
            $baseBranch = $config->get('base') ?: 'master';
        }

        $tracker = $this->getIssueTracker();
        $issue = $tracker->getIssue($issueNumber);

        $slugTitle = $this->getHelper('text')->slugify(
            sprintf(
                '%s %s',
                $issueNumber,
                $issue['title']
            )
        );

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        $gitHelper->remoteUpdate($remote);
        $gitHelper->checkout($remote.'/'.$baseBranch);
        $gitHelper->checkout($slugTitle, true);

        $url = $tracker->getIssueUrl($issueNumber);

        $this->getHelper('gush_style')->success("Issue {$url} taken!");

        return self::COMMAND_SUCCESS;
    }
}
