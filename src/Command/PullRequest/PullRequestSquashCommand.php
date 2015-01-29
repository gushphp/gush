<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestSquashCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:squash')
            ->setDescription('Squashes all commits of a PR')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number to squash')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command squashes all commits of a PR:

    <info>$ gush %command.name% 12</info>

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
        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        $base = $pr['base']['ref'];
        $head = $pr['head']['ref'];

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */

        $gitHelper->squashCommits($base, $head);
        $gitHelper->pushToRemote('origin', $head, true, true);

        $adapter->createComment($prNumber, '(PR squashed)');

        $output->writeln('<info>PR has been squashed!<info>');

        return self::COMMAND_SUCCESS;
    }
}
