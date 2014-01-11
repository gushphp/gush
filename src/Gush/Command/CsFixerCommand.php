<?php

/*
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

/**
 * Run cs-fixer
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class CsFixerCommand extends BaseCommand
{
    const DEFAULT_FIXER_LINE = 'php-cs-fixer fix .';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:fix')
            ->setDescription('Run cs-fixer and commits fixes')
            ->addArgument('fixer_line', InputArgument::OPTIONAL, 'Custom fixer command', self::DEFAULT_FIXER_LINE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixerLine = $input->getArgument('fixer_line');

        if ($fixerLine === self::DEFAULT_FIXER_LINE) {
            $this->ensurePhpCsFixerInstalled();
        }

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
                    'line' => $fixerLine,
                    'allow_failures' => true
                ],
                [
                    'line' => 'git add .',
                    'allow_failures' => true
                ],
                [
                    'line' => 'git commit -am cs-fixer',
                    'allow_failures' => true
                ]
            ]
        );
    }
}
