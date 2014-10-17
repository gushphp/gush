<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IssueShowCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:show')
            ->setDescription('Shows given issue')
            ->addOption('issue_number', 'i', InputOption::VALUE_REQUIRED, 'Issue number')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command shows issue details for either the current or the given organization
and repo:

    <info>$ gush %command.name% --issue_number=60</info>

You can also call the command without options to pick up the current issue from the branch name:

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
        if (null === $issueNumber = $input->getOption('issue_number')) {
            $issueNumber = $this->getHelper('git')->getIssueNumber();
        }

        $tracker = $this->getIssueTracker();
        $issue = $tracker->getIssue($issueNumber);

        $output->writeln(
            sprintf(
                PHP_EOL.'Issue #%s (%s): by %s [%s]',
                $issue['number'],
                $issue['state'],
                $issue['user'],
                $issue['assignee']
            )
        );

        if ($issue['pull_request']) {
            $output->writeln('Type: Pull Request');
        } else {
            $output->writeln('Type: Issue');
        }
        $output->writeln('Milestone: '.$issue['milestone']);
        if ($issue['labels'] > 0) {
            $output->writeln('Labels: '.implode(', ', $issue['labels']));
        }
        $output->writeln(
            [
                'Title: '.$issue['title'],
                'Link: '.$issue['url'],
                '',
                $issue['body'],
            ]
        );

        return self::COMMAND_SUCCESS;
    }
}
