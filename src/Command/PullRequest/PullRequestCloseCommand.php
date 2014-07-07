<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestCloseCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:close')
            ->setDescription('Closes a pull request')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number to be closed')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'Closing comment')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command closes a Pull Request for either the current or the given organization
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
        $prNumber = $input->getArgument('pr_number');
        $closingComment = $input->getOption('message');

        $adapter = $this->getAdapter();

        $adapter->closePullRequest($prNumber);

        if ($input->getOption('message')) {
            $adapter->createComment($prNumber, $closingComment);
        }

        $url = $adapter->getPullRequest($prNumber)['url'];
        $output->writeln("Closed {$url}");

        return self::COMMAND_SUCCESS;
    }
}
