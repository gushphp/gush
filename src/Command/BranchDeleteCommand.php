<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
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
 * Deletes remote branch for the given pull request
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchDeleteCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:delete')
            ->setDescription('Deletes remote branch with the current or given name')
            ->addArgument('branch_name', InputArgument::OPTIONAL, 'Branch name to remove')
            ->addArgument(
                'other_organization',
                InputArgument::OPTIONAL,
                'Organization (default to username) where the branch will be removed'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command deletes remote and local branch with the current or given name:

    <info>$ gush %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$currentBranchName = $input->getArgument('branch_name')) {
            $currentBranchName = $this->getHelper('git')->getBranchName();
        }

        $org = $this->getParameter('authentication')['username'];
        if (null !== $input->getArgument('other_organization')) {
            $org = $input->getArgument('other_organization');
        }

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf(
                        'git push -u %s :%s',
                        $org,
                        $currentBranchName
                    ),
                    'allow_failures' => true,
                ]
            ]
        );

        $output->writeln(sprintf('Branch %s/%s has been deleted!', $org, $currentBranchName));

        return self::COMMAND_SUCCESS;
    }
}
