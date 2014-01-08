<?php

/*
 * This file is part of the Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run php-cs-fixer
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PhpCsFixerCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:fixer')
            ->setDescription('Run php-cs-fixer and commits fixes')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ensurePhpCsFixerInstalled();

        $this->runCommands([
                [
                    'line' => 'git add .',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git commit -am wip',
                    'allow_failures' => true
                ],
                [
                    'line' => 'php-cs-fixer fix .',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git add .',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git commit -am php-cs-fixer',
                    'allow_failures' => true
                ]
            ]
        );
    }
}
