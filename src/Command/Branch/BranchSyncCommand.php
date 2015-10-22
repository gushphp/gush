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
use Gush\Feature\GitFolderFeature;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Gush\Operation\GitSyncOperation;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchSyncCommand extends BaseCommand implements GitRepoFeature, GitFolderFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:sync')
            ->setDescription('Synchronises the local branch with a remote branch')
            ->addArgument('source_branch', InputArgument::OPTIONAL, 'Remote branch-name to use as source (base)')
            ->addArgument('source_remote', InputArgument::OPTIONAL, 'Remote to pull from (defaults to [your-username])')
            ->addArgument(
                'dest_remote',
                InputArgument::OPTIONAL,
                'Remote to push changes to (defaults to [your-username])'
            )
            ->addArgument(
                'dest_branch',
                InputArgument::OPTIONAL,
                'Remote branch to push changes to (defaults to the source_branch)'
            )
            ->addOption(
                'strategy',
                's',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Strategy to use for the synchronization process, eg: %s',
                    implode(
                        ', ',
                        [GitSyncOperation::SYNC_FORCE, GitSyncOperation::SYNC_SMART, GitSyncOperation::SYNC_SMART_MERGE]
                    )
                ),
                GitSyncOperation::SYNC_SMART
            )
            ->addOption(
                'force-push',
                'f',
                InputOption::VALUE_NONE,
                'Allow to push with force when this required (only used for smart and smart-merge), not recommended for shared branches!'
            )
            ->addOption(
                'no-push',
                'np',
                InputOption::VALUE_NONE,
                'Skip pushing of local changes, cannot be used in combination with --force-push'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command synchronises the current local branch with a remote branch.

    <info>$ gush %command.name% master gushphp</info>

This command supports various strategies for synchronizing, by default the 'smart' strategy
is used which is best suited for most cases. But sometimes you may want to use something else.

You can change the strategy with the <info>--strategy</> option.

    <info>$ gush %command.name% --strategy=smart master gushphp</info>

Gush supports the following strategies:

* force: Forces the local branch to equal the remote version (this is the equivalent of using git reset --hard remote/branch).

* smart: Determines what is best to use, pull --rebase or push.

* smart-merge: Same as 'smart' but uses merge instead of rebase for pulling-in changes.

<comment>Note: Gush will refuse to push with force (when a rebase is performed) unless you either explicitly
provide a "dest_remote" or supply the use '--force-push' option.</>

    <info>$ gush %command.name% --strategy=smart --force-push master gushphp</info>

If you rather don't want to push your local changes you can use the <comment>--no-push</> option.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('no-push') && $input->getOption('force-push')) {
            throw new UserException('Can only use --no-push or --force-push (not both).');
        }

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');

        $sourceRemote = $input->getArgument('source_remote');
        $sourceBranch = $input->getArgument('source_branch');

        if (null === $sourceRemote) {
            $sourceRemote = $this->getParameter($input, 'authentication')['username'];
        }

        if (null === $sourceBranch) {
            $sourceBranch = $gitHelper->getActiveBranchName();
        }

        $destRemote = $input->getArgument('dest_remote');
        $destBranch = $input->getArgument('dest_branch');

        if (null === $destRemote) {
            $destRemote = $this->getParameter($input, 'authentication')['username'];
        }

        if (null === $destBranch) {
            $destBranch = $sourceBranch;
        }

        $options = 0;

        if ($input->getOption('no-push')) {
            $options |= GitSyncOperation::DISABLE_PUSH;
        } elseif (null !== $input->getArgument('dest_remote') || (bool) $input->getOption('force-push')) {
            $options |= GitSyncOperation::FORCE_PUSH;
        }

        $syncOperation = $gitHelper->createSyncOperation();
        $syncOperation->setLocalRef($gitHelper->getActiveBranchName());
        $syncOperation->setRemoteRef($sourceRemote, $sourceBranch);
        $syncOperation->setRemoteDestination($destRemote, $destBranch);
        $syncOperation->sync($input->getOption('strategy'), $options);

        $this->getHelper('gush_style')->success(
            sprintf('Branch "%s" has been synchronised with remote "%s".', $sourceBranch, $sourceRemote)
        );

        return self::COMMAND_SUCCESS;
    }
}
