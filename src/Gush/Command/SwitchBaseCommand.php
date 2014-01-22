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

use Gush\Feature\GitHubFeature;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class SwitchBaseCommand extends BaseCommand implements GitHubFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:switch')
            ->setDescription('Switch the base of the PR to another one')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number to be switched')
            ->addArgument('base_branch', InputArgument::OPTIONAL, 'Name of the new base branch to switch the PR to', 'master')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $org = $input->getOption('org');
        $repo = $input->getOption('repo');
        $prNumber = $input->getArgument('pr_number');
        $baseBranch = $input->getArgument('base_branch');

        // squash to only cherry-pick once
        $command = $this->getApplication()->find('pull-request:squash');
        $input = new ArrayInput(
            [
                'command' => 'pull-request:squash',
                'pr_number' => $prNumber
            ]
        );
        $command->run($input, $output);

        // get old base and sha1 from old PR
        $client = $this->getGithubClient();
        $pr = $client->api('pull_request')->show($org, $repo, $prNumber);
        $commitSha1 = $pr['head']['sha'];
        $branchName = $pr['head']['ref'];

        // close PR
        $command = $this->getApplication()->find('issue:close');
        $input = new ArrayInput(
            [
                'command' => 'issue:close',
                'issue_number' => $prNumber
            ]
        );
        $command->run($input, $output);

        $commands = [
            [
                'line' => sprintf('git remote update'),
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git checkout -b %s-switched origin/%s', $branchName, $baseBranch),
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git cherry-pick %s', $commitSha1),
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git push -u origin :%s', $branchName),
                'allow_failures' => true
            ],
            [
                'line' => sprintf('git push -u origin %s-switched', $branchName),
                'allow_failures' => true
            ]
        ];

        $this->runCommands($commands);

        $command = $this->getApplication()->find('pull-request:create');
        $input = new ArrayInput(
            [
                'command' => 'pull-request:create',
                'base_branch' => $baseBranch
            ]
        );
        $command->run($input, $output);

        return self::COMMAND_SUCCESS;
    }
}
