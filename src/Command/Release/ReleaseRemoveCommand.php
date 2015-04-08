<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\Release;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseRemoveCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('release:remove')
            ->setDescription('Removes a release')
            ->addArgument('id', InputArgument::REQUIRED, 'ID of the release')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command removes a given release:

    <info>$ gush %command.name% 3</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $repo = $input->getOption('repo');
        $org = $input->getOption('org');

        $this->getAdapter()->removeRelease($id);

        $this->getHelper('gush_style')->success(
            sprintf(
                'Release %s on %s/%s was deleted.',
                $id,
                $org,
                $repo
            )
        );

        return self::COMMAND_SUCCESS;
    }
}
