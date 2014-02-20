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

use Gush\Feature\GitHubFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Forks upstream repository and creates local remote
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchForkCommand extends BaseCommand implements GitHubFeature
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
                'org',
                InputArgument::REQUIRED,
                'Organization (default to username) to where we will fork the upstream repository'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command forks the current upstream repository:

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
        $adapter = $this->getAdapter();
        if (null !== $input->getArgument('org')) {
            $org = $input->getArgument('org');
        } else {
            $org = $this->getHelper('git')->getUsername();
        }
        $fork = $adapter->createFork($org);
        $repo = $input->getOption('repo');

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf(
                        'git remote add %s %s',
                        $org,
                        $fork['remote_url']
                    ),
                    'allow_failures' => true
                ]
            ],
            $output
        );

        $output->writeln(
            sprintf(
                'Forked repository %s/%s to %s/%s',
                'cordoval',
                $repo,
                $org,
                $repo
            )
        );

        return self::COMMAND_SUCCESS;
    }
}
