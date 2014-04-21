<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Gush\Feature\GitHubFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Closes an issue
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class IssueCloseCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('issue:close')
            ->setDescription('Closes an issue')
            ->addArgument('issue_number', InputArgument::REQUIRED, 'Issue number to be closed')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Closing comment')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command closes an issue for either the current or the given organization
and repository:

    <info>$ gush %command.full_name% 12 -m"let's try to keep it low profile guys."</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueNumber    = $input->getArgument('issue_number');
        $closingComment = $input->getOption('message');

        $adapter = $this->getAdapter();

        $adapter->closeIssue($issueNumber);

        if ($input->getOption('message')) {
            $adapter->createComment($issueNumber, $closingComment);
        }

        $url = $adapter->getIssueUrl($issueNumber);
        $output->writeln("Closed {$url}");

        return self::COMMAND_SUCCESS;
    }
}
