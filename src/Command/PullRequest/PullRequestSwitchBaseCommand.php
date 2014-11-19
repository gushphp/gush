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
use Gush\Feature\GitRepoFeature;
use Gush\Helper\GitHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        $adapter = $this->getAdapter();
        $pr = $adapter->getPullRequest($prNumber);

        $currentBase = $pr['base']['ref'];
        $branchName = $pr['head']['ref'];

        $adapter->closePullRequest($prNumber);

        $gitHelper = $this->getHelper('git');
        /** @var GitHelper $gitHelper */
        $gitHelper->remoteUpdate();
        $gitHelper->switchBranchBase($branchName, $currentBase, $baseBranch, $branchName.'-switched');
        $gitHelper->pushRemote('origin', ':'.$branchName);
        $gitHelper->pushRemote('origin', $branchName.'-switched', true);

        $command = $this->getApplication()->find('pull-request:create');
        $input = new ArrayInput(
            [
                'command' => 'pull-request:create',
                '--base' => $baseBranch,
                '--source-branch' => $branchName.'-switched',
                '--org' => $input->getOption('org'),
                '--repo' => $input->getOption('repo'),
            ]
        );
        $command->run($input, $output);

        return self::COMMAND_SUCCESS;
    }
}
