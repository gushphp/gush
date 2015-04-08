<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Repository;

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
            ->setName('repo:create')
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

        $this->getHelper('gush_style')->success(
            [
                sprintf('Repository "%s" was created.', $name),
                'Git: '.$result['git_url'],
                'Web: '.$result['html_url'],
            ]
        );

        return self::COMMAND_SUCCESS;
    }
}
