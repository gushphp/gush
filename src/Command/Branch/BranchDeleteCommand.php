<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Exception\UserException;
use Gush\Feature\GitDirectoryFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchDeleteCommand extends BaseCommand implements GitRepoFeature, GitDirectoryFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:delete')
            ->setDescription('Deletes the current branch, or the branch with the given name')
            ->addArgument('branch_name', InputArgument::OPTIONAL, 'Optional branch name to delete')
            ->addArgument(
                'organization',
                InputArgument::OPTIONAL,
                'Organization (defaults to username) where the branch will be deleted'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Attempts to delete the branch even when permissions detected are insufficient'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command deletes the current or given remote branch on
the organization (defaults to username):

    <info>$ gush %command.name%</info>

Note: The "organization" argument defaults to your username (the forked repository) not
the organization you would normally provide using the --org option.

For security reasons it's not directly possible to delete the "master" branch,
use the <comment>--force</comment> option to force a delete, use with caution!
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if (null === $input->getArgument('branch_name')) {
            $input->setArgument('branch_name', $this->getHelper('git')->getActiveBranchName());
        }

        if (null === $input->getArgument('organization')) {
            $input->setArgument('organization', $this->getParameter($input, 'authentication')['username']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $branchName = $input->getArgument('branch_name');
        $org = $input->getArgument('organization');

        if (!$this->getHelper('gush_style')->confirm(
            sprintf('Are you sure you want to delete "%s/%s"? this action cannot be reverted!', $org, $branchName),
            false
        )) {
            throw new UserException('User aborted.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branchName = $input->getArgument('branch_name');
        $org = $input->getArgument('organization');

        $this->guardProtectedBranches($input, $branchName);

        $this->getHelper('git')->deleteRemoteBranch($org, $branchName);

        $this->getHelper('gush_style')->success(
            sprintf('Branch %s/%s has been deleted!', $org, $branchName)
        );

        return self::COMMAND_SUCCESS;
    }

    private function guardProtectedBranches(InputInterface $input, $branchName)
    {
        if ($input->getOption('force')) {
            return;
        }

        if ($branchName === 'master') {
            throw new UserException(
                sprintf('The "%s" branch is protected and cannot be deleted without the "--force" option!', $branchName)
            );
        }
    }
}
