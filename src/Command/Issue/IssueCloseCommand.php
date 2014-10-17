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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IssueCloseCommand extends BaseCommand implements GitRepoFeature
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

    <info>$ gush %command.name% 12 -m"let's try to keep it low profile guys."</info>

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

        $tracker = $this->getIssueTracker();

        $tracker->closeIssue($issueNumber);

        if ($input->getOption('message')) {
            $tracker->createComment($issueNumber, $closingComment);
        }

        $url = $tracker->getIssueUrl($issueNumber);
        $output->writeln("Closed {$url}");

        return self::COMMAND_SUCCESS;
    }
}
