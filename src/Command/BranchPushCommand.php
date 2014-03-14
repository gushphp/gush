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
 * Pushes a local branch and applies tracking to user's fork
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class BranchPushCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:push')
            ->setDescription('Pushes and tracks the current local branch into user own fork')
            ->addArgument(
                'other_organization',
                InputArgument::OPTIONAL,
                'Organization (default to username) to where to push the branch'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command pushes the current local branch into user own fork:

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
        $branchName = $this->getHelp('git')->getBranchName();

        if (null !== $input->getArgument('other_organization')) {
            $org = $input->getArgument('other_organization');
        } else {
            $org = $this->getParameter('authentication')['username'];
        }

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf('git push -u %s %s', $org, $branchName),
                    'allow_failures' => true
                ]
            ],
            $output
        );

        $output->writeln(
            sprintf('Branch pushed to %s/%s', $org, $branchName)
        );

        return self::COMMAND_SUCCESS;
    }
}
