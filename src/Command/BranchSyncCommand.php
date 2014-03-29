<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gush\Feature\GitHubFeature;

/**
 * Syncs a local branch with its upstream version
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchSyncCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:sync')
            ->setDescription('Syncs local branch with its upstream version')
            ->addArgument('branch_name', InputArgument::OPTIONAL, 'Branch name to sync')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command syncs local branch with its upstream version:

    <info>$ gush %command.full_name% develop</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stashedBranchName = $this->getHelper('git')->getBranchName();

        if (null !== $input->getArgument('branch_name')) {
            $branchName = $input->getArgument('branch_name');
        } else {
            $branchName = $stashedBranchName;
        }

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => 'git remote update',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git checkout '.$branchName,
                    'allow_failures' => true
                ],
                [
                    'line' => 'git reset --hard HEAD~1',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git pull -u origin '.$branchName,
                    'allow_failures' => true
                ],
                [
                    'line' => 'git checkout '.$stashedBranchName,
                    'allow_failures' => true
                ]
            ]
        );

        $output->writeln(sprintf('Branch %s has been synced upstream!', $branchName));

        return self::COMMAND_SUCCESS;
    }
}
