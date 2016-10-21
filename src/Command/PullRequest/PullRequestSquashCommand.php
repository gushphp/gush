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
use Gush\Exception\UserException;
use Gush\Feature\GitDirectoryFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestSquashCommand extends BaseCommand implements GitRepoFeature, GitDirectoryFeature
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
The <info>%command.name%</info> command squashes all commits of a pull-request:

    <info>$ gush %command.name% 12</info>

Make sure you are the pull-requests\'s source-branch repository owner
or have been granted push access to the repository.

Note: This will squash all commits in the pull-request, if a local branch
exists with the same name this branch is used for the operation.
Else the remote branch is checked out locally.
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

        if ($gitHelper->branchExists($sourceBranch)) {
            $this->squashLocalBranch($sourceOrg, $sourceBranch, $baseOrg.'/'.$baseBranch);
        } else {
            $this->squashRemoteBranch($sourceOrg, $sourceBranch, $baseOrg.'/'.$baseBranch);
        }

        $gitHelper->pushToRemote($sourceOrg, $sourceBranch, false, true);
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
                ),
            ]
        );

        if (!$this->getHelper('gush_style')->confirm('Do you want to squash the pull-request and push?')) {
            throw new UserException('User aborted.');
        }
    }

    private function squashLocalBranch($remote, $sourceBranch, $base, bool $ignoreMultipleAuthors = false)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->checkout($sourceBranch);

        $status = $gitHelper->getRemoteDiffStatus($remote, $sourceBranch);

        if (GitHelper::STATUS_NEED_PULL === $status) {
            $this->getHelper('gush_style')->note(
                sprintf('Your local branch "%s" is outdated, running git pull.', $sourceBranch)
            );

            $gitHelper->pullRemote($remote, $sourceBranch);
        } elseif (GitHelper::STATUS_DIVERGED === $status) {
            $gitHelper->restoreStashedBranch();

            throw new UserException([
                'Cannot safely perform the squash operation.',
                sprintf('Your local and remote version of branch "%s" have differed.', $sourceBranch),
                'Please resolve this problem manually.'
            ]);
        }

        $gitHelper->squashCommits($base, $sourceBranch, $ignoreMultipleAuthors);
    }

    private function squashRemoteBranch($remote, $sourceBranch, $base, bool $ignoreMultipleAuthors = false)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->checkout($remote.'/'.$sourceBranch);
        $gitHelper->checkout($sourceBranch, true);

        $gitHelper->squashCommits($base, $sourceBranch, $ignoreMultipleAuthors);
    }

}
