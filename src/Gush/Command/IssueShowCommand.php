<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Shows an issue
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueShowCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:show')
            ->setDescription('Shows given issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Issue number')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command shows issue details for either the current or the given organization
and repo:

    <info>$ gush %command.full_name% 60</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');

        $issueNumber = $input->getArgument('issue_number');

        $client = $this->getGithubClient();
        $issue = $client->api('issue')->show($org, $repo, $issueNumber);

        $output->writeln(sprintf(
            "\nIssue #%s (%s): by %s [%s]",
            $issue['number'],
            $issue['state'],
            $issue['user']['login'],
            $issue['assignee']['login']
        ));

        if (isset($issue['pull_request'])) {
            $output->writeln('Type: Pull Request');
        } else {
            $output->writeln('Type: Issue');
        }
        $output->writeln('Milestone: '.$issue['milestone']['title']);
        if ($issue['labels'] > 0) {
            $labels = array_map(
                function ($label) {
                    return $label['name'];
                },
                $issue['labels']
            );
            $output->writeln('Labels: '.implode(', ', $labels));
        }
        $output->writeln('Title: '.$issue['title']);
        $output->writeln('');
        $output->writeln($issue['body']);

        return self::COMMAND_SUCCESS;
    }
}
