<?php

/**
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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Takes an issue from the GitHub repository issue list
 */
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
            ->addArgument('base_branch', InputArgument::OPTIONAL, 'Name of the base branch to checkout from', 'master')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command takes an issue from GitHub repository list:

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
        $issueNumber = $input->getArgument('issue_number');
        $baseBranch = $input->getArgument('base_branch');

        $adapter = $this->getIssueTracker();
        $issue = $adapter->getIssue($issueNumber);

        $slugTitle = $this->getHelper('text')->slugify(
            sprintf(
                '%d %s',
                $issueNumber,
                $issue['title']
            )
        );

        $commands = [
            [
                'line' => 'git remote update',
                'allow_failures' => true,
            ],
            [
                'line' => sprintf('git checkout %s/%s', 'origin', $baseBranch),
                'allow_failures' => true,
            ],
            [
                'line' => sprintf('git checkout -b %s', $slugTitle),
                'allow_failures' => true,
            ],
        ];

        $this->getHelper('process')->runCommands($commands);

        $url = $adapter->getIssueUrl($issueNumber);
        $output->writeln("Issue {$url} taken!");

        return self::COMMAND_SUCCESS;
    }
}
