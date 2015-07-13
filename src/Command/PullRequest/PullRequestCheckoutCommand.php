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
use Gush\Feature\GitFolderFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestCheckoutCommand extends BaseCommand implements GitRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:checkout')
            ->setDescription('Checks out a pull request as local branch')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'Pull Request number to be checked-out')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command is used to check a pull-request out from the organization.

When the branch already exists Gush will check if the remote-upstream is the same
as the source organization of the pull-request and check out the local branch instead.

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = $this->getAdapter();
        $prNumber = $input->getArgument('pr_number');

        $pullRequest = $adapter->getPullRequest($prNumber);

        /** @var GitConfigHelper $gitConfig */
        $gitConfig = $this->getHelper('git_config');
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $gitHelper->guardWorkingTreeReady();

        $sourceOrg = $pullRequest['head']['user'];
        $sourceRepo = $pullRequest['head']['repo'];
        $sourceBranch = $pullRequest['head']['ref'];

        $gitConfig->ensureRemoteExists($sourceOrg, $sourceRepo);
        $gitHelper->remoteUpdate($sourceOrg);

        if ($gitHelper->branchExists($sourceBranch)) {
            if ($gitConfig->getGitConfig('branch.'.$sourceBranch.'.remote') !== $sourceOrg) {
                throw new UserException(
                    [
                        sprintf(
                            'A local branch named "%s" already exists but it\'s remote is not "%s".',
                            $sourceBranch,
                            $sourceOrg
                        ),
                        'Rename the local branch to resolve this conflict.'
                    ]
                );
            }

            $gitHelper->checkout($sourceBranch);
            $gitHelper->pullRemote($sourceOrg, $sourceBranch);
        } else {
            $gitHelper->checkout($sourceOrg.'/'.$sourceBranch);
            $gitHelper->checkout($sourceBranch, true);

            $gitConfig->setGitConfig('branch.'.$sourceBranch.'.remote', $sourceOrg, true);
        }

        $this->getHelper('gush_style')->success("Successfully checked-out pull-request {$pullRequest['url']} in '{$sourceBranch}'");

        return self::COMMAND_SUCCESS;
    }
}
