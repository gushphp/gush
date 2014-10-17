<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestFixerCommand extends BaseCommand
{
    const DEFAULT_FIXER_LINE = 'php-cs-fixer fix .';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:fixer')
            ->setDescription('Run cs-fixer and commits fixes')
            ->addArgument('fixer_line', InputArgument::OPTIONAL, 'Custom fixer command', self::DEFAULT_FIXER_LINE)
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> runs the coding style fixer and commits fix:

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
        $fixerLine = $input->getArgument('fixer_line');

        if ($fixerLine === self::DEFAULT_FIXER_LINE) {
            $this->getHelper('process')->probePhpCsFixer();
        }

        $this->getHelper('process')->runCommands(
            [
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
                ],
            ]
        );

        $output->writeln('CS fix committed and pushed!');

        return self::COMMAND_SUCCESS;
    }
}
