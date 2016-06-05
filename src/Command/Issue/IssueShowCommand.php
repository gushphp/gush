<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Issue;

use Gush\Command\BaseCommand;
use Gush\Exception\UserException;
use Gush\Feature\IssueTrackerRepoFeature;
use Gush\Helper\StyleHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IssueShowCommand extends BaseCommand implements IssueTrackerRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:show')
            ->setDescription('Shows given issue')
            ->addArgument('issue', InputArgument::OPTIONAL, 'Issue number')
            ->addOption('with-comments', null, InputOption::VALUE_NONE, 'Display comments from this issue')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command shows issue details for either the current or the given organization
and repo:

    <info>$ gush %command.name% 60</info>

You can also call the command without the issue argument to pick up the current issue from the branch name:

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
        if (null === $issueNumber = $input->getArgument('issue')) {
            $issueNumber = $this->getHelper('git')->getIssueNumber();
        }

        $comments = [];
        $tracker = $this->getIssueTracker();
        $issue = $tracker->getIssue($issueNumber);
        if (true === $input->getOption('with-comments')) {
            $comments = $tracker->getComments($issueNumber);
        }

        /** @var StyleHelper $styleHelper */
        $styleHelper = $this->getHelper('gush_style');
        $styleHelper->title(
            sprintf(
                'Issue #%s - %s by %s [<fg='.'%s>%s</>]',
                $issue['number'],
                $issue['title'],
                $issue['user'],
                'closed' === $issue['state'] ? 'red' : 'green',
                $issue['state']
            )
        );

        $styleHelper->detailsTable(
            [
                ['Org/Repo', $input->getOption('issue-org').' / '.$input->getOption('issue-project')],
                ['Link', $issue['url']],
                ['Labels', implode(', ', $issue['labels']) ?: '<comment>None</comment>'],
                ['Milestone', $issue['milestone'] ?: '<comment>None</comment>'],
                ['Assignee', $issue['assignee'] ?: '<comment>None</comment>'],
            ]
        );

        $styleHelper->section('Body');
        $styleHelper->text(explode("\n", $issue['body']));

        if (true === $input->getOption('with-comments') && count($comments) > 0) {
            $output->writeln(
                [
                    '',
                    str_pad('Comments ', 100, '-'),
                    '',
                ]
            );

            foreach ($comments as $comment) {
                $output->writeln(
                    [
                        sprintf(
                            'Comment #%s by %s on %s',
                            $comment['id'],
                            $comment['user']['login'],
                            $comment['created_at']->format('r')
                        ),
                        'Link: '.$comment['url'],
                        '',
                        wordwrap($comment['body'], 100),
                        str_pad('', 10, '-'),
                    ]
                );
            }
        }

        return self::COMMAND_SUCCESS;
    }
}
