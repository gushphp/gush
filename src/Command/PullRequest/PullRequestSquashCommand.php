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
use Gush\Helper\GitConfigHelper;
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
            ->setDescription('Squashes all commits of a pull request')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'pull-request number to squash')
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

        $sourceOrg = $pr['head']['user'];
        $branchName = $pr['head']['ref'];

        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');
        $gitConfigHelper->ensureRemoteExists($pr['user'], $pr['head']['repo']);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->remoteUpdate($sourceOrg);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->squashCommits($base, $head);
        $gitHelper->pushToRemote('origin', $head, false, true);

        $adapter->createComment($prNumber, '(PR squashed)');

        $this->getHelper('gush_style')->success('Pull-request has been squashed!');

        return self::COMMAND_SUCCESS;
    }
}
