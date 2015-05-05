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
use Gush\Exception\UserException;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'no-local-sync',
                null,
                InputOption::VALUE_NONE,
                'Do not sync the local branch with the squashed version'
            )
            ->addArgument('pr_number', InputArgument::REQUIRED, 'pull-request number to squash')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command squashes all commits of a pull-request:

    <info>$ gush %command.name% 12</info>

Make sure you are the pull-requests\'s source-branch repository owner
or have been granted push access to the repository.

Note: This will squash all commits in the pull-request and (when a local branch exists with
the same name) sync your local source branch with the squashed version.
You can skip this sync-process using the <comment>--no-local-sync</> option.
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

        $this->checkOwner($input, $pr);

        $baseOrg = $pr['base']['user'];
        $baseBranch = $pr['base']['ref'];

        $sourceOrg = $pr['head']['user'];
        $sourceBranch = $pr['head']['ref'];

        /** @var GitConfigHelper $gitConfigHelper */
        $gitConfigHelper = $this->getHelper('git_config');
        $gitConfigHelper->ensureRemoteExists($baseOrg, $pr['base']['repo']);
        $gitConfigHelper->ensureRemoteExists($sourceOrg, $pr['head']['repo']);

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->remoteUpdate($baseOrg);
        $gitHelper->remoteUpdate($sourceOrg);

        $gitHelper->stashBranchName();
        $gitHelper->checkout($sourceBranch);
        $gitHelper->checkout($tmpBranch = $gitHelper->createTempBranch($sourceBranch), true);
        $gitHelper->squashCommits($baseOrg.'/'.$baseBranch, $tmpBranch);
        $gitHelper->pushToRemote($sourceOrg, $tmpBranch.':'.$sourceBranch, false, true);

        if (!$input->getOption('no-local-sync') && $gitHelper->branchExists($sourceBranch)) {
            $gitHelper->checkout($sourceBranch);
            $gitHelper->reset($tmpBranch, 'hard');
        }

        $gitHelper->restoreStashedBranch();

        $adapter->createComment($prNumber, '(PR squashed)');

        $this->getHelper('gush_style')->success('Pull-request has been squashed!');

        return self::COMMAND_SUCCESS;
    }

    private function checkOwner(InputInterface $input, array $pr)
    {
        // Don't check when non-interactive or when pull-request is on target org already
        if (!$input->isInteractive() || $pr['head']['user'] === $input->getOption('org')) {
            return;
        }

        if ($pr['head']['user'] === $this->getParameter($input, 'authentication')['username']) {
            return;
        }

        $this->getHelper('gush_style')->note(
            [
                'You are not the owner of the repository pull-requests\'s source-branch.',
                sprintf(
                    'Make sure you have push access to the "%s/%s" repository before you continue.',
                    $pr['head']['user'],
                    $pr['head']['repo']
                )
            ]
        );

        if (!$this->getHelper('gush_style')->confirm('Do you want to squash the pull-request and push?')) {
            throw new UserException('User aborted.');
        }
    }
}
