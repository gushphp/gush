<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Feature\GitDirectoryFeature;
use Gush\Helper\GitHelper;
use Gush\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PullRequestFixerCommand extends BaseCommand implements GitDirectoryFeature
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

        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');

        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelper('process');

        if ($fixerLine === self::DEFAULT_FIXER_LINE) {
            $fixerLine = $processHelper->probePhpCsFixer().substr(self::DEFAULT_FIXER_LINE, 12);
        }

        $gitHelper->add('.');

        if (!$gitHelper->isWorkingTreeReady()) {
            $this->getHelper('gush_style')->note(
                'Your working tree has uncommitted changes, committing changes with "WIP" as message.'
            );

            $gitHelper->commit('wip', GitHelper::COMMIT_ALL);
        }

        $processHelper->runCommand($fixerLine, true);

        $gitHelper->add('.');

        if (!$gitHelper->isWorkingTreeReady()) {
            $gitHelper->commit('cs-fixer', GitHelper::COMMIT_ALL);
        }

        $this->getHelper('gush_style')->success('CS fixes committed!');

        return self::COMMAND_SUCCESS;
    }
}
