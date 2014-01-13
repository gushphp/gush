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

/**
 * Show issue
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueShowCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:show')
            ->setDescription('Show given issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Issue number')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command show details of the given issue for either the current or the given organization
and repository:

    <info>$ php %command.full_name% 60</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $input->getArgument('org');
        $repository = $input->getArgument('repo');
        $issueNumber = $input->getArgument('issue_number');

        $client = $this->getGithubClient();

        $issue = $client->api('issue')->show($organization, $repository, $issueNumber);

        $output->writeln('');
        $output->writeln(
            'Issue #'.$issue['number'].' ('.$issue['state'].'): by '.$issue['user']['login']
            .' ['.$issue['assignee']['login'].']'
        );
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
