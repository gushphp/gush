<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Branch;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitConfigHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BranchForkCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:fork')
            ->setDescription('Forks current upstream repository')
            ->addArgument(
                'other_organization',
                InputArgument::OPTIONAL,
                'Organization (default to username) to where we will fork the upstream repository'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command forks the upstream repository and creates local remote:

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
        $adapter = $this->getAdapter();
        $orgFork = $input->getArgument('other_organization');
        $username = $this->getParameter('authentication')['username'];

        $fork = $adapter->createFork($orgFork);

        $repo = $input->getOption('repo');
        $sourceOrg = $input->getOption('org');
        $targetOrg = $orgFork ?: $username;

        $gitConfigHelper = $this->getHelper('git_config');
        /** @var GitConfigHelper $gitConfigHelper */

        $gitConfigHelper->setRemote($targetOrg, $fork['git_url']);

        $output->writeln(
            sprintf(
                'Forked repository %s/%s into %s/%s',
                $sourceOrg,
                $repo,
                $targetOrg,
                $repo
            )
        );

        return self::COMMAND_SUCCESS;
    }
}
