<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Repository;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RepositoryCreateCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('repo:create')
            ->setDescription('Creates a new repository')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the new repository')
            ->addArgument('description', InputArgument::OPTIONAL, 'Repository description')
            ->addArgument('homepage', InputArgument::OPTIONAL, 'Repository homepage')
            ->addOption(
                'private',
                null,
                InputOption::VALUE_NONE,
                'Make a private repository (may require a plan upgrade)'
            )
            ->addOption(
                'no-init',
                null,
                InputOption::VALUE_NONE,
                'Create an empty repository instead of having an "initial commit"'
            )
            ->addOption(
                'target-org',
                null,
                InputOption::VALUE_REQUIRED,
                'Target organization (defaults to your username)'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command creates a new repository:

    <info>$ gush %command.name% my-package</info>

By default the repository will be created in your "personal" organization (your username)
to create the repository in a specific organization use the <comment>--target-org</> option.

    <info>$ gush %command.name% --target-org=my-org my-package</info>

If you want to create a private repository (non open-source) use the <comment>--private</> option:

    <info>$ gush %command.name% --private my-package</info>

Note: Private repositories may not be supported by the used adapter or only in paid/higher plans.

Last, if you don't want to initialize the repository (with an initial commit) use
the <comment>--no-init</> option:

    <info>$ gush %command.name% --no-init my-package</info>

This will leave the repository empty, you need to push at least one commit
before any pull requests can be opened.
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
            !$input->getOption('private'),
            $organization = $input->getOption('target-org') ?: null,
            $hasIssues = true,
            $hasWiki = false,
            $hasDownloads = false,
            $teamId = null,
            $autoInit = !$input->getOption('no-init')
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
