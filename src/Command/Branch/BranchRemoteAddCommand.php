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

class BranchRemoteAddCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('branch:remote:add')
            ->setDescription('Adds a remote with url used from adapter')
            ->addArgument(
                'other_organization',
                InputArgument::REQUIRED,
                'Organization or username the remote will point to'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command adds a remote with url used from adapter:

    <info>$ gush %command.name% sstok</info>

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
        $org = $input->getArgument('other_organization');
        $username = $this->getParameter('authentication')['username'];

        $fork = $adapter->createFork($org);

        $repo = $input->getOption('repo');
        $vendorName = $input->getOption('org');

        $this->getHelper('process')->runCommands(
            [
                [
                    'line' => sprintf(
                        'git remote add %s %s',
                        $org ?: $username,
                        $fork['git_url']
                    ),
                    'allow_failures' => true,
                ]
            ]
        );

        $output->writeln(sprintf('Added remote for %s', $org));

        return self::COMMAND_SUCCESS;
    }
}
