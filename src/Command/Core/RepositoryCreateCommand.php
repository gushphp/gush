<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Core;

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RepositoryCreateCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('core:create')
            ->setDescription('Quickly spins a repository')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the new repository')
            ->addArgument('description', InputArgument::OPTIONAL, 'Repository description')
            ->addArgument('homepage', InputArgument::OPTIONAL, 'Repository homepage' )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command spins a repository:

    <info>$ gush %command.name% my-package</info>

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

        $name = $input->getArgument('name');
        $description = $input->getArgument('description');
        $homepage = $input->getArgument('homepage');

        $result = $adapter->createRepo(
            $name,
            $description,
            $homepage,
            true,
            $organization = null,
            $hasIssues = true,
            $hasWiki = false,
            $hasDownloads = false,
            $teamId = null,
            $autoInit = true
        );

        $output->writeln(
            sprintf('Repository created %s <info>%s</info>', $result['git_url'], $result['http_url'])
        );

        return self::COMMAND_SUCCESS;
    }
}
