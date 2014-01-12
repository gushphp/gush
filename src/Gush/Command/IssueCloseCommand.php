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
 * Close issue
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueCloseCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('issue:close')
            ->setDescription('Closes an issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Issue number to be closed')
            ->addArgument('org', InputArgument::OPTIONAL, 'Name of the GitHub organization', $this->getVendorName())
            ->addArgument('repo', InputArgument::OPTIONAL, 'Name of the GitHub repository', $this->getRepoName())
            ->setHelp(<<<EOF
The <info>%command.name%</info> command closes an issue for either the current or the given organization
and repository:

    <info>$ php %command.full_name% 12</info>
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

        $parameters = [
            'state' => 'closed',
        ];
        $client->api('issue')->update($organization, $repository, $issueNumber, $parameters);

        $output->writeln("Closed https://github.com/{$organization}/{$repository}/issues/{$issueNumber}");

        return self::COMMAND_SUCCESS;
    }
}
