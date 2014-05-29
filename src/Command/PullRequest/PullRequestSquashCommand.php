<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Command\PullRequest;

use Gush\Command\BaseCommand;
use Gush\Feature\GitRepoFeature;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Squashes all commits of a PR
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestSquashCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:squash')
            ->setDescription('Squashes all commits of a PR')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number to squash')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command squashes all commits of a PR:

    <info>$ gush %command.name% 12</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $prNumber = $input->getArgument('pr_number');
        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        $username = $this->getParameter('authentication')['username'];
        if ($pr['head']['user'] !== $username) {
            $output->writeln('You cannot squash PRs that are not your own.');

            return self::COMMAND_FAILURE;
        }

        $base = $pr['base']['ref'];
        $head = $pr['head']['ref'];

        $commands = [
            [
                'line' => 'git remote update',
                'allow_failures' => true
            ],
            [
                'line' => 'git checkout '.$head,
                'allow_failures' => true
            ],
            [
                'line' => 'git reset --soft '.$base,
                'allow_failures' => true
            ],
            [
                'line' => 'git commit -am '.$head,
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git push -u %s %s -f', $username, $head),
                'allow_failures' => true
            ],
        ];

        $this->getHelper('process')->runCommands($commands);

        $output->writeln('PR has been squashed!');

        return self::COMMAND_SUCCESS;
    }
}
