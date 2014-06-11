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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Switches the base of a pull request
 *
 * @author Luis Cordova <cordoval@gmail.com>
 */
class PullRequestSwitchBaseCommand extends BaseCommand implements GitRepoFeature
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull-request:switch-base')
            ->setDescription('Switch the base of the PR to another one')
            ->addArgument('pr_number', InputArgument::REQUIRED, 'PR number to be switched')
            ->addArgument(
                'base_branch',
                InputArgument::OPTIONAL,
                'Name of the new base branch to switch the PR to',
                'master'
            )
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> command switches the base of the given pull request:

    <info>$ gush %command.name% 12 2.3</info>

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
        $baseBranch = $input->getArgument('base_branch');

        // squashes to only cherry-pick once
        $command = $this->getApplication()->find('pull-request:squash');
        $input = new ArrayInput(
            [
                'command' => 'pull-request:squash',
                'pr_number' => $prNumber
            ]
        );
        $command->run($input, $output);

        // gets old base and sha1 from old PR
        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);
        $commitSha1 = $pr['head']['sha'];
        $branchName = $pr['head']['ref'];

        // closes PR
        $adapter->closePullRequest($prNumber);

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
                'allow_failures' => false
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

        $this->getHelper('process')->runCommands($commands);

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
