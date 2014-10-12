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
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command spins a repository:

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

        $adapter->createRepo($name, $description, $homepage, true);

        $output->writeln(
            sprintf("%s: %s   <info>%s</info>", $id, $issue['title'], $issue['url'])
        );

        return self::COMMAND_SUCCESS;
    }
}
