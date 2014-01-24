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
use Gush\Feature\GitHubFeature;

/**
 * Squashes all commits of a PR
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestSquashCommand extends BaseCommand implements GitHubFeature
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

    <info>$ gush %command.full_name% 12</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');;
        $prNumber = $input->getArgument('pr_number');

        $client = $this->getGithubClient();
        $pr = $client->api('pull_request')->show($org, $repo, $prNumber);
        $base = $pr['base']['ref'];
        $head = $pr['head']['ref'];

        $github = $this->getParameter('github');
        $username = $github['username'];

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

        $this->runCommands($commands);

        return self::COMMAND_SUCCESS;
    }
}
