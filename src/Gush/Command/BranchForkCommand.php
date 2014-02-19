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
 * Forks upstream
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

        $result = $adapter->fork();

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => 'git push -u origin',
                    'allow_failures' => true
                ]
            ],
            $output
        );

        $output->writeln('Repository forked!');

        return self::COMMAND_SUCCESS;
    }
}
