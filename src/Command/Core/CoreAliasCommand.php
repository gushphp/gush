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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Outputs help and alias snippet for wrapping gush on git
 */
class CoreAliasCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('core:alias')
            ->setDescription('Outputs help and alias snippet for wrapping gush on git')
            ->addOption('s', '-s', InputOption::VALUE_NONE, "Outputs only snippet")
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> outputs an alias snippet to wrap Gush will use:

    <info>$ gush %command.name% -s</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('s')) {
            $output->writeln('# Wrap git automatically by adding the following to ~/.zshrc:');
            $output->writeln('');
        }

        $output->writeln('eval "$(gush alias -s)"');
    }
}
