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
use Gush\Feature\GitFolderFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestSwitchBaseCommand extends BaseCommand implements GitRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:switch-base')
            ->setDescription('Switch the base of the PR to another one')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number to be switched')
            ->addArgument(
                'base_branch',
                InputArgument::OPTIONAL,
                'Name of the new base branch to switch the PR to',
                'master'
            )
            ->addOption(
                'force-new-pr',
                null,
                InputOption::VALUE_NONE,
                'Create a new PR, even when the used adapter supports switching the base'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command switches the base of the given pull request
to the given base. This will preserve all commits made after the current base.

    <info>$ gush %command.name% 12 2.3</info>

When switching the base is not supported by the used adapter, a new pull request is
created instead. You can overwrite this behaviour with <comment>--force-new-pr</comment> to create
a new pull request (even if the adapter supports switching).

    <info>$ gush %command.name% --force-new-pr 12 2.3</info>

<fg=yellow;options=bold>To ensure a proper rebase process it's advised to make sure the
pull-request branch is up-to-date with the current base!</>
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
        $baseBranch = $input->getArgument('base_branch');

        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        $currentBase = $pr['base']['ref'];
        $branchName = $pr['head']['ref'];
        $sourceOrg = $pr['head']['user'];

        if ($currentBase === $baseBranch) {
            $this->getHelper('gush_style')->error(
                sprintf('Pull-request base-branch is already based on %s!', $baseBranch)
            );

            return self::COMMAND_SUCCESS;
        }

        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');
        $gitConfigHelper->ensureRemoteExists($pr['base']['user'], $pr['base']['repo']);
        $gitConfigHelper->ensureRemoteExists($sourceOrg, $pr['head']['repo']);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->remoteUpdate($sourceOrg);
        $gitHelper->remoteUpdate($pr['base']['user']);
        $gitHelper->switchBranchBase(
            $branchName,
            $pr['base']['user'].'/'.$currentBase,
            $pr['base']['user'].'/'.$baseBranch,
            $branchName.'-switched'
        );

        $gitHelper->pushToRemote($sourceOrg, $branchName.'-switched', true);
        $gitHelper->pushToRemote($sourceOrg, ':'.$branchName);

        $switchPr = $adapter->switchPullRequestBase(
            $prNumber,
            $baseBranch,
            $sourceOrg.':'.$branchName.'-switched',
            $input->getOption('force-new-pr')
        );

        if ($prNumber === $switchPr['number']) {
            $adapter->createComment($prNumber, sprintf('(PR base switched to %s)', $baseBranch));

            $this->getHelper('gush_style')->success('Pull-request base-branch has been switched!');
        } else {
            $adapter->createComment($prNumber, sprintf('(PR replaced by %s)', $switchPr['html_url']));
            $adapter->closePullRequest($prNumber);

            $this->getHelper('gush_style')->success(
                [
                    'Pull-request base-branch could not be switched, a new pull-request has been opened instead: ',
                    $switchPr['html_url'],
                ]
            );
        }

        return self::COMMAND_SUCCESS;
    }
}
