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

class IssueAssignCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:assign')
            ->setDescription('Assigns an issue to a user')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Number of the issue')
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the assignee')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command assigns an issue to a user:

    <info>$ gush %command.name% 3 cordoval</info>

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
        $username = $input->getArgument('username');

        $adapter = $this->getIssueTracker();
        $adapter->updateIssue($issueNumber, ['assignee' => $username]);

        $url = $adapter->getIssueUrl($issueNumber);
        $output->writeln("Issue {$url} was assigned to {$username}!");

        return self::COMMAND_SUCCESS;
    }
}
