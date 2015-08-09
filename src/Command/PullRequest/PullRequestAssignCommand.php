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
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestAssignCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:assign')
            ->setDescription('Assigns a pull-request to a user')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Number of the pull request')
            ->addArgument('username', InputArgument::REQUIRED, 'Username of the assignee')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command assigns a pull request to a user:

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
        $issueNumber = $input->getArgument('pr_number');
        $username = $input->getArgument('username');

        $adapter = $this->getAdapter();
        $adapter->updatePullRequest($issueNumber, ['assignee' => $username]);

        $url = $adapter->getPullRequest($issueNumber)['url'];
        $this->getHelper('gush_style')->success("Pull-request {$url} is now assigned to \"{$username}\"!");

        return self::COMMAND_SUCCESS;
    }
}
