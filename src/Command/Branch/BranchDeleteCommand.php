<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BranchDeleteCommand extends BaseCommand implements GitRepoFeature
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
                'Organization (defaults to username) where the branch will be removed'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command deletes remote and local branch with the current or given name:

    <info>$ gush %command.name%</info>

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
            $currentBranchName = $this->getHelper('git')->getActiveBranchName();
        }

        $org = $input->getArgument('other_organization');
        if (null === $org) {
            $org = $this->getParameter('authentication')['username'];
        }

        $this->getHelper('git')->pushToRemote($org, ':'.$currentBranchName, true);

        $output->writeln(sprintf('Branch %s/%s has been deleted!', $org, $currentBranchName));

        return self::COMMAND_SUCCESS;
    }
}
