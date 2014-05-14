<?php

/**
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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Forks upstream repository and creates local remote
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
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
The <info>%command.name%</info> command forks the current upstream repository:

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
        if (null !== $input->getArgument('other_organization')) {
            $org = $input->getArgument('other_organization');
        } else {
            $org = $this->getParameter('authentication')['username'];
        }
        $fork = $adapter->createFork($org);
        $repo = $input->getOption('repo');
        $vendorName = $input->getOption('org');

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf(
                        'git remote add %s %s',
                        $org,
                        $fork['git_url']
                    ),
                    'allow_failures' => true
                ]
            ]
        );

        $output->writeln(
            sprintf(
                'Forked repository %s/%s into %s/%s',
                $vendorName,
                $repo,
                $org,
                $repo
            )
        );

        return self::COMMAND_SUCCESS;
    }
}
